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
      'map_origin' => '49.505, -29.09',
      'map_scale' => '1.7',
      // API key for Mapbox, tile provider for Leaflet. https://www.mapbox.com/studio/account/tokens/
      'mapbox_token' => 'pk.eyJ1IjoibXVybXVyYXRpb25zIiwiYSI6ImNqeGN2MTIxYTAwMWQzdnBhODlmOHRyeXEifQ.KkzeMmUS2suuPI_n3l7jAA',
      'feed_storage_path' => plugin_dir_path(__FILE__).'feeds/feeds.json'
    );
  }

  public function includeDependencies(){
    // Move feed reading include here, unless we ditch it
  }

  /* Update all locally-stored node data from the index and nodes, adding new matching nodes and updating existing nodes */
  public function updateNodes(){

    $settings = $this->env->load_settings();

    // Filters are stored in the environment as multilevel array
    $filters = $settings['filters'];

    if(is_array($filters)){
      foreach ($filters as $key => $condition) {
        if(in_array($condition[0],$this->index_fields)){
          $index_filters[] = $condition;
        }
      }
    }

    $update_since = $settings['update_time'];

    if($settings['ignore_date'] != 'true'){
      $index_filters[] = array('updated','isGreaterThan',$update_since);
    }

    $query = array(
      'action' => 'get_nodes',
      'conditions' => $index_filters
    );

    // Query the index to collect URLs of (possibly) wanted nodes that are recently updated
    $index_nodes = $this->indexRequest($query);

    if(!$index_nodes){
      $this->setNotice("Could not connect to the index","error");
      return false;
      /* TODO: Even if the index is out, could still query from stored nodes */
    }

    $failed_nodes = array();
    $fetched_nodes = array();
    $matched_nodes = array();
    $saved_nodes = array();

    $results = array(
      'nodes_from_index' => array(),
      'failed_nodes' => array(),
      'fetched_nodes' => array(),
      'matched_nodes' => array(),
      'saved_nodes' => array()
    );

    // Query the nodes to collect the data
    if(is_array($index_nodes)){

      if(count($index_nodes) > 0){

        foreach ($index_nodes as $key => $data) {

          $url = $data['apiUrl'];

          $results['nodes_from_index'][] = $url;

          // Get the JSON from the node
          $node_data = $this->nodeRequest($url);

          if(!$node_data){
            $results['failed_nodes'][] = $url;
          }else{

            $results['fetched_nodes'][] = $url;

            $node_data_ar = json_decode($node_data, true);

            // TODO: remove this. It seems that in some cases extra enclosing brackets lead to error-free json_decode calls returning strings of JSON, raather than array. This is a fallback.

            if(!is_array($node_data_ar)){
              $node_data_ar = json_decode($node_data_ar, true);
            }

            $node_data_ar['apiUrl'] = $url;

            $matched = true;

            if(is_array($filters)){
              foreach ($filters as $condition) {
                if(!$this->check_node_condition($node_data_ar,$condition)){
                  $matched = false;
                  //llog("Failed condition.</b> Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
                }else{
                  //llog("Matched condition. Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
                }
              }
            }

            if($matched == true){
              $results['matched_nodes'][] = $url;

              // Save the node to local DB
              $result = $this->env->save_node($node_data_ar);

              if($result){
                $results['saved_nodes'][] = $url;
              }
            }
          }
        }
      }
    }

    $message = "Nodes updated. ".count($results['nodes_from_index'])." nodes fetched from index. ".count($results['failed_nodes'])." failed. ".count($results['fetched_nodes'])." nodes returned results. ".count($results['matched_nodes'])." nodes matched filters. ".count($results['saved_nodes'])." nodes saved. ";

    if(count($results['saved_nodes']) > 0){
      $class = 'success';
    }else{
      $class = 'notice';
    }

    $this->setNotice($message,$class);

    // Update the local update time in the environment
    $this->env->save_setting('update_time',time());

  }

  public function setNotice($message,$class = 'notice'){
    $this->env->set_notice($message,$class);
  }

  /* Show the directory */
  public function showDirectory(){
    $nodes = $this->env->load_nodes();

    //llog($nodes, 'Nodes loaded from env...');
    //llog("Updating nodes...");

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

    //This API recently changed (https://docs.mapbox.com/help/troubleshooting/migrate-legacy-static-tiles-api)

    $html = $this->env->leaflet_scripts();

    $map_origin = $this->settings['map_origin'];
    $map_scale = $this->settings['map_scale'];

    $html .= '<div id="murmurations-map" class="murmurations-map"></div>'."\n";
    $html .= '<script type="text/javascript">'."\n";
    $html .= "var murmurations_map = L.map('murmurations-map').setView([".$map_origin."], $map_scale);\n";

    $html .= "L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
    tileSize: 512,
    maxZoom: 18,
    zoomOffset: -1,
    id: 'mapbox/streets-v11',
    accessToken: '".$this->settings['mapbox_token']."'
}).addTo(murmurations_map);\n";

    foreach ($nodes as $key => $node) {

      //llog($node);

      if(is_numeric($node->murmurations['lat']) && is_numeric($node->murmurations['lon'])){

          $popup = trim($this->env->load_template('map_node_popup',$node));

          $lat = $node->murmurations['lat'];
          $lon = $node->murmurations['lon'];

          $html .= "var marker = L.marker([".$lat.", ".$lon."]).addTo(murmurations_map);\n";
          $html .= "marker.bindPopup(\"$popup\");\n";

       }
    }

    $html .= "</script>\n";

    return $html;
  }

  /* Called by shortcode to display feed on front end */
  public function showFeeds(){

    //$this->env->delete_all_feed_items();

    //$this->updateFeeds();
    //LazyLog::flush();
    //exit();
    //$feeds = json_decode(file_get_contents($this->settings['feed_storage_path']),true);
/*
    if(safe_count($feeds) < 1){
      return "No feeds to display";
    }

    //llog($feeds,"Feeds in display");

    $feeds_html = "<div class=\"murmurations-feeds\">";

    foreach ($feeds as $item) {
      $feeds_html .= $this->env->load_template('feed_item',$item);
    }

    $feeds_html .= "</div>";

    //llog($feeds_html,'Feeds HTML');

    //return $feeds_html;

*/

  }

  public function schedule(){

  }

  /* Update locally-stored feed data from the feed-providing nodes */
  public function updateFeeds(){

    $this->env->delete_all_feed_items();

    $feed_items = array();
    //$since_time = $this->env->load_setting('feed_update_time');

    $max_feed_items = $this->env->settings['max_feed_items'];
    $max_feed_items_per_node = $this->env->settings['max_feed_items_per_node'];

    // Get the locally stored nodes
    $nodes = $this->env->load_nodes();

    $results = array(
      'nodes_with_feeds' => 0,
      'feed_items_fetched' => 0,
      'feed_items_saved' => 0
    );

    foreach ($nodes as $node) {

      //echo llog($node,"Node for fetching feed");

      if($node->murmurations['feed']){
        $feed_url = $node->murmurations['feed'];

        $feed = $this->feedRequest($feed_url);

        // For some reason this comes with an *xml key that misbehaves...
        $feed = array_shift($feed);

        if(is_array($feed)){
          $node_item_count = 0;

          $results['nodes_with_feeds']++;

          //echo llog($feed);

          //exit();

          /* RSS includes multiple <item> elements. The RSS parser adds a single ['item'],
          with numerically indexed elements for each item from the RSS. But, if there is only one item in the feed, it doesn't do this, and ['item'] is an array of item properties, not items */

          if(!$feed['item'][0]){
            $temp = $feed['item'];
            unset($feed['item']);
            $feed['item'][0] = $temp;
          }

          foreach ($feed['item'] as $item) {
            if(is_array($item)){
              $node_item_count++;
              // Add the info about the node to each item
              $item['node_info'] = $node->murmurations;
              $feed_items[] = $item;
              if($node_item_count == $max_feed_items_per_node) break;

            }else{
              echo llog($feed,"Strange non-array feed item.");
            }
          }
        }else{
          $results['broken_feeds']++;
          $this->setNotice("This feed could not be parsed: $feed_url",'warning');
        }
      }
    }

    // Sort reverse chronologically
    usort($feed_items, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    $results['feed_items_fetched'] = count($feed_items);

    $count = 0;

    foreach ($feed_items as $key => $item) {

      $result = $this->env->save_feed_item($item);

      $results['feed_items_saved']++;
      if($results['feed_items_saved'] == $max_feed_items){
        break;
      }
    }

    $this->setNotice("Feeds updated. ".$results['feed_items_fetched']." feed items fetched from ".$results['nodes_with_feeds']." nodes. ".$results['feed_items_saved']." feed items saved.",'success');

  }



  /* Update a node in the local DB from the node's JSON-LD */
  private function updateNode($url){

    // Get the JSON from the node
    $node_data = $this->nodeRequest($url);

    if(!$node_data){
      return false;
    }else{
      // Parse and save to environment
      $node_data_ar = json_decode($node_data, true);

      $conditions = $this->env->load_setting('filters');

      $matched = true;

      if(is_array($conditions)){
        foreach ($conditions as $condition) {
          if(!$this->check_node_condition($node_data_ar,$condition)){
            $matched = false;
            llog("Failed condition.</b> Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
          }else{
            llog("Matched condition. Node: ".print_r($node_data_ar,true)." \n Cond:".print_r($condition,true));
          }
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

    $url = $this->loadSetting('index_url');

    $fields_string = http_build_query($query);

    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);

    return json_decode($result,true);

  }

  /* Collect updated RSS feed data */
  private function feedRequest($url,$since_time = null){

    // Get simpleXML of feed
    try {
      $rss = Feed::loadRss($url);
    } catch (\Exception $e) {
      $this->setNotice("Error connecting to feed URL: ".$url,'warning');
      $this->log_error("Couldn't load feed");
    }

    $ar = xml2array($rss);

    llog($ar,"Feed array");

    return $ar;
  }

  public function showAdminSettingsPage(){

    if($_POST['action']){
      check_admin_referer( 'murmurations_ag_actions_form');
      if($_POST['action'] == 'update_murms_feed_items'){
        $this->updateFeeds();
      }
      if($_POST['action'] == 'update_nodes'){
        $this->updateNodes();
      }
      if($_POST['action'] == 'delete_all_nodes'){
        $this->env->delete_all_nodes();
      }
    }

   echo "<h1>Murmurations Aggregator</h1>";
   ?>
   <form method="POST">
   <?php
   wp_nonce_field( 'murmurations_ag_actions_form' );
   ?>
   <button type="submit" name="action" class="murms-update murms-has-icon" value="update_nodes"><i class="murms-icon murms-icon-update"></i>Update nodes</button>
   <button type="submit" name="action" class="murms-update murms-has-icon" value="update_murms_feed_items"><i class="murms-icon murms-icon-update"></i>Update feeds</button>

   <button type="submit" name="action" class="murms-delete murms-has-icon" value="delete_all_nodes"><i class="murms-icon murms-icon-delete"></i>Delete all stored nodes</button>

 </form>
 <?php


    $this->env->show_admin_settings_page();
  }


  public function importNodes(){
    /* What this (or associated pieces) needs to do:
    Add a tab to the admin page
    Show a file upload form
    Process uploaded CSV data
        -- No: for now, since this is relatively rare operation, well skip the interface:
          Upload a file to the "imports" directory
          Load a separate file that's only for importing that:
            1) Opens the import data file
            2) Parses it into an array
            4) Writes out to JSON files and adds to index (optionally) <-- this will need to be batched in some way, otherwise the index requests will get out of control on non-tiny data sets
            3) Adds to WP DB

            */


  }
  public function log_error($error){
    if(is_callable('llog')){
      llog($error);
    }
  }

}



function xml2array ( $xmlObject, $out = array () )
{
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ||  is_array ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
}

/* Count an array that might not be an array */
if(!function_exists('safe_count')){
  function safe_count($a){
    if(is_array($a)){
      return count($a);
    }else{
      return false;
    }
  }
}


 ?>
