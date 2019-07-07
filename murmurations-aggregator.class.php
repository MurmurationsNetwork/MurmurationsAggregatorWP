<?php

/* Function called by weekly cron */
function murmurations_index_update(){
  $ag = new Murmurations_Aggregator();
  $ag->updateNodes();
}

/*  Called by daily cron */
function murmurations_feed_update(){
   $ag = new Murmurations_Aggregator();
   $ag->updateFeeds();
}

/* Core aggregator class */
class Murmurations_Aggregator{
  var $env ;
  var $settings = array();

  // This needs to replaced with pulling the base schema (or add on schemas) and determining which fields can be queried to the index. Queries that include non-index fields don't match nodes.

  var $index_fields = array('url','nodeTypes','updated');


  public function __construct(){
    $this->env = new Murmurations_Aggregator_WP();
    $this->settings = array(
      'map_origin' => '51.505, -0.09',
      'map_scale' => '4',
      // API key for Mapbox, tile provider for Leaflet. https://www.mapbox.com/studio/account/tokens/
      'mapbox_token' => 'pk.eyJ1IjoibXVybXVyYXRpb25zIiwiYSI6ImNqeGN2MTIxYTAwMWQzdnBhODlmOHRyeXEifQ.KkzeMmUS2suuPI_n3l7jAA'
    );
  }

  public function includeDependencies(){
    // Move feed reading include here, unless we ditch it
  }

  /* Update all locally-stored node data from the index and nodes, adding new matching nodes and updating existing nodes */
  public function updateNodes(){

    // Only pull data for nodes that have updated since we last collected data
    $update_since = $this->env->load_setting('update_time');

    // Filters are stored in the environment as multilevel array
    $filters = $this->env->load_setting('filters');

    foreach ($filters as $key => $condition) {
      if(in_array($condition[0],$this->index_fields)){
        $index_filters[] = $condition;
      }
    }

    $settings = $this->env->load_settings();

    if($settings['ignore_date'] != 'true'){
      $index_filters[] = array('updated','isGreaterThan',$update_since);
    }

    $query = array(
      'action' => 'get_nodes',
      'conditions' => $index_filters
    );

    // Query the index to collect URLs of (possibly) wanted nodes that are recently updated
    $index_nodes = $this->indexRequest($query);

    llog($index_nodes,"Fetched from index");

    // Then query the nodes themselves to collect the data, and update the node in the local DB
    if(is_array($index_nodes)){
      if(count($index_nodes) > 0){
        foreach ($index_nodes as $key => $data) {
          $this->updateNode($data['apiUrl']);
        }
      }
    }

    // Update the local update time in the environment
    $this->env->save_setting('update_time',time());

  }

  /* Show the directory */
  public function showDirectory(){
    $nodes = $this->env->load_nodes();
    llog($nodes, 'Nodes loaded from env...');

    llog("Updating nodes...");

    $this->updateNodes();

    return $this->env->format_nodes($nodes);
  }

  public function showMap(){
    $nodes = $this->env->load_nodes();

    /* Because of the cross-origin stuff, these don't fit WP's queue paradigm. In future, we should use this method, but for now loading scripts as HTML in the head via env
    $this->env->add_css(array(
      'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.css",
      'integrity' => "sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==",
      'crossorigin' => "");

    $this->env->add_script(array(
        'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.js",
        'integrity' => "sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==",
        'crossorigin' => "");
    */

    //$this->env->queue_leaflet_scripts();

    $html = $this->env->leaflet_scripts();

    $map_origin = $this->settings['map_origin'];
    $map_scale = $this->settings['map_scale'];

    $html .= '<div id="murmurations-map" class="murmurations-map"></div>'."\n";
    $html .= '<script type="text/javascript">'."\n";
    $html .= "var murmurations_map = L.map('murmurations-map').setView([".$map_origin."], $map_scale);\n";

    $html .= "L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
    maxZoom: 18,
    id: 'mapbox.streets',
    accessToken: '".$this->settings['mapbox_token']."'
}).addTo(murmurations_map);\n";

    foreach ($nodes as $key => $node) {

      llog($node);

      if($node->murmurations['lat'] && $node->murmurations['lon']){

          //$popup = "test";

          $popup = trim($this->env->load_template('map_node_popup',$node));

          $lat = $node->murmurations['lat'];
          $lon = $node->murmurations['lon'];

          $html .= "var marker = L.marker([".$lat.", ".$lon."]).addTo(murmurations_map);\n";         $html .= "marker.bindPopup(\"$popup\");\n";

       }
    }

    $html .= "</script>\n";

    return $html;
  }

  public function showFeed(){

  }

  public function schedule(){

  }

  /* Update locally-stored feed data from the feed-providing nodes */
  public function updateFeeds(){
    $feeds_array = array();
    $since_time = $this->env->load_setting('feed_update_time');
    $feeds_to_update = array();

    // Get the locally stored nodes
    $nodes = $this->env->load_nodes();

    // Get feed URLs from nodes
    foreach ($nodes as $key => $node) {
      if($node['feeds']){
        if(safe_count($node['feeds']) > 0){
           foreach ($node['feeds'] as $key => $url) {
             $feeds_to_update[] = $url;
           }
        }
      }
    }

    // If we have feeds to update
    if(count($feeds_to_update) > 0){
      foreach ($feeds_to_update as $url) {
        $feed = $this->feedRequest($url);
        //TODO: Add processing here
        $feeds_array[] = $feed;
      }

      $feeds_json = json_encode($feeds_array);

      //TODO: Consider improved solution for storing feed data
      file_put_contents($this->setting("feed_storage_path"),$feeds_json);
    }
  }

  /* Update a node in the local DB from the node's JSON-LD */
  private function updateNode($url){
    // Get the JSON from the node
    $node_data = $this->nodeRequest($url);

    llog($node_data,"Got node data");

    if(!$node_data){
      // TODO: When this is a genuine error with the request to the node, a message needs to be sent to the index reporting a node that's offline/otherwise not working properly
    }else{
      // Parse and save to environment
      $node_data_ar = json_decode($node_data, true);

      $conditions = $this->env->load_setting('filters');

      $matched = true;

      foreach ($conditions as $condition) {
        if(!$this->check_node_condition($node_data_ar,$condition)){
          $matched = false;
          llog("Failed condition.</b> Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
        }else{
          llog("Matched condition. Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
        }
      }
      if($matched == true){
        $this->env->save_node($node_data_ar);
      }
    }
  }

  private function check_node_condition($node,$condition){

    llog($node,"Checking node");

    list($subject, $predicate, $object) = $condition;

    if(!isset($node[$subject])) return false;

    switch ($predicate){
      case 'equals':
        if($node[$subject] == $object) return true;
        break;
      case 'isGreaterThan':
        if($node[$subject] > $object) return true;
        break;
      case 'isLessThan':
        if($node[$subject] < $object) return true;
        break;
      case 'isIn':
        if(strpos($object,$node[$subject]) !== false) return true;
        break;
      case 'includes':
        if(strpos($node[$subject],$object) !== false) return true;
        break;
       // This is where we need to add some very clever geographic matching things

      default: return false;
    }

  }


  /* Do a query to a Murmurations node */
  private function nodeRequest($url){

    llog($url,"Making node request to");

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    return $result;
  }

  public function loadSetting($setting){
    return $this->env->load_setting($setting);
  }

  /* Do a query to the Murmurations index */
  private function indexRequest($query){

    llog($query,"Making index request with");

    $url = $this->loadSetting('index_url');

    llog($url,"Making index request to");

    $fields_string = http_build_query($query);

    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);

    llog($result,"cURL result");

    return json_decode($result,true);

  }

  /* Collect updated RSS feed data */
  private function feedRequest($url,$since_time = null){

    // Get simpleXML of feed
    $rss = Feed::loadRss($url);

    $ar = xml2array($rss);

    return $ar;
  }

  public function showAdminSettingsPage(){
    $this->env->show_admin_settings_page();
  }

}



function xml2array ( $xmlObject, $out = array () )
{
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ||  is_array ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
}



 ?>
