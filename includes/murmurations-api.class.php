<?php
namespace Murmurations\Aggregator;


class Murmurations_API{

  public static $logging_handler = false;

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

    if ($options['api_basic_auth_user'] && $options['api_basic_auth_pass']){
        $curl_upass = $options['api_basic_auth_user'] . ":" . $options['api_basic_auth_pass'];
        if ( $options['api_key'] ) {
          $url .= '?' . http_build_query(array('api_key' => $options['api_key']));
        }
    }else{
      if ( $options['api_key'] ) {
        $curl_upass = $options['api_key'] . ":";
      }
    }

    if($curl_upass){
      curl_setopt($ch, CURLOPT_USERPWD, $curl_upass);
    }

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);

    if($result === false){
      self::log("No result returned from cURL request to node. cURL error: ".curl_error($ch));
      return false;
    }

    return $result;
  }

  public static function getIndexJson($url,$query,$options = null){

    $ch = curl_init();

    // For handling data sources that are behind basic auth:
    // If basic auth information is set, add it to cURL request...
    if ($options['api_basic_auth_user'] && $options['api_basic_auth_pass']){
        $curl_upass = $options['api_basic_auth_user'] . ":" . $options['api_basic_auth_pass'];
        // And put the api key, if present, into the query (not recommended)
        if ( $options['api_key'] ) {
          $query['api_key'] = $options['api_key'];
        }
    }else{
      // Otherwise use the cURL basic auth parameters for the api key (recommended)
      if ( $options['api_key'] ) {
        $curl_upass = $options['api_key'] . ":";
      }
    }

    $fields_string = http_build_query($query);

    if($curl_upass){
      curl_setopt($ch, CURLOPT_USERPWD, $curl_upass);
    }

    curl_setopt($ch,CURLOPT_URL, $url . '?' .$fields_string);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_FAILONERROR, true);

    $result = curl_exec($ch);

    if($result === false){
      self::log("No result returned from cURL request to index. cURL error: ".curl_error($ch));
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
      self::log("Couldn't load feed");
    }

    $ar = xml2array($rss);

    return $ar;
  }

  public static function xml2array($xmlObject, $out = array()){
    foreach ((array) $xmlObject as $index => $node ){
      $out[$index] = (is_object($node) || is_array($node)) ? xml2array($node) : $node;
    }
    return $out;
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

  private static function log($content, $meta = null, $type = 'notice'){
    if(is_callable(self::$logging_handler)){
      call_user_func(self::$logging_handler, $content, $meta, $type = 'notice');
    }else{
      echo '<pre>';
      echo $meta ? $meta . ': ' : '';
      echo ( is_array( $content ) || is_object( $content ) ) ? print_r( (array) $content, true ) : $content;
      echo '</pre>';
    }
  }
}







 ?>
