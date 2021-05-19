<?php


class Murmurations_API{
  var $settings = array();

  // This needs to replaced with pulling the base schema (or add on schemas) and determining which fields can be queried to the index. Queries that include non-index fields don't match nodes.

  var $index_fields = array('url','nodeTypes','updated');


  public function __construct(){


  }

  public static function checkNodeCondition($node,$condition){

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

      default: return false;
    }

  }


  public static function getNodeJson($url,$options = null){

    $ch = curl_init();

    if($options['api_key']){
      curl_setopt($ch, CURLOPT_USERPWD, $options['api_key'] . ":");
    }

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);

    if($result === false){
      self::logError("No result returned from cURL request to node. cURL error: ".curl_error($ch));
      return false;
    }

    return $result;
  }

  public static function getIndexJson($url,$query,$options = null){

    $fields_string = http_build_query($query);

    $ch = curl_init();

    if($options['api_key']){
      curl_setopt($ch, CURLOPT_USERPWD, $options['api_key'] . ":");
    }

    curl_setopt($ch,CURLOPT_URL, $url . '?' .$fields_string);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_FAILONERROR, true);

    $result = curl_exec($ch);

    if($result === false){
      self::logError("No result returned from cURL request to index. cURL error: ".curl_error($ch));
      return false;
    }else{
      return $result;
    }
  }

  /* Collect updated RSS feed data */
  private function feedRequest($url,$since_time = null){

    // Get simpleXML of feed
    try {
      $rss = Feed::loadRss($url);
    } catch (\Exception $e) {
      self::setNotice("Error connecting to feed URL: ".$url,'warning');
      self::logError("Couldn't load feed");
    }

    $ar = xml2array($rss);

    llog($ar,"Feed array");

    return $ar;
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
  public function logError($error){
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
