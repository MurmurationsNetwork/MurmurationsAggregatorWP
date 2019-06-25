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


  public function __construct(){
    $this->env = new Murmurations_Aggregator_WP();
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

    $filters[] = array('updated','isGreaterThan',$update_since);

    $query = array(
      'action' => 'get_nodes',
      'conditions' => $filters
    );

    // Query the index to collect URLs of (possibly) wanted nodes that are recently updated
    $nodes_to_fetch = $this->indexRequest($query);

    llog($nodes_to_fetch,"Fetched from index");

    // Then query the nodes themselves to collect the data, and update the node in the local DB
    foreach ($nodes_to_fetch as $key => $data) {
      $this->updateNode($data['apiUrl']);
    }

    // Update the local update time in the environment
    $this->env->save_setting('update_time',time());

  }

  // Show the directory. For architectural reasons we have to go through this, and then the environment, rather than the other way around...
  public function showDirectory(){
    $nodes = $this->env->load_nodes();
    llog($nodes, 'Nodes loaded from env...');

    llog("Updating nodes...");

    $this->updateNodes();

    return $this->env->format_nodes($nodes);
  }

  public function showMap(){
    $nodes = $this->env->load_nodes();
    return $this->env->format_nodes($nodes);
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
      $this->env->save_node($node_data_ar);
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

}



function xml2array ( $xmlObject, $out = array () )
{
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ||  is_array ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
}



 ?>
