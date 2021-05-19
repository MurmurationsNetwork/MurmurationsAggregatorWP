<?php
error_reporting (E_ALL);
ini_set('dispay_errors', true);
define('WP_USE_THEMES', false);
$base = dirname(dirname(__FILE__));
require($base.'/../../wp-load.php');
if(!current_user_can('manage_options')) {
  die("Permission denied");
}

if($_GET['t']){
  if ($_GET['t'] && method_exists('MurmsAggregatorTests',$_GET['t'])){
    $f = $_GET['t'];
    if($_GET['p']){
      $result = MurmsAggregatorTests::$f($_GET['p']);
    }else{
      $result = MurmsAggregatorTests::$f();
    }
    echo llog($result,$f.'('.$p.')');
  }
}

class MurmsAggregatorTests{

  public static function showNodes(){
    $config =  array(
      'schema_file' => plugin_dir_path(__FILE__).'schemas/gen_ecovillages_v0.0.1.json',
      'field_map_file' => plugin_dir_path(__FILE__).'schemas/gen_ecovillages_field_map.json',
    );

    $ag = new Murmurations_Aggregator_WP($config);

    $ag->load_nodes();

    $out = array();

    foreach ($ag->nodes as $id => $node) {
      $out[$id] = $node->data;
    }

    return $out;

  }

  public static function updateNode(){

    $config =  array(
      'schema_file' => plugin_dir_path(__FILE__).'schemas/gen_ecovillages_v0.0.1.json',
      'field_map_file' => plugin_dir_path(__FILE__).'schemas/gen_ecovillages_field_map.json',
    );

    $ag = new Murmurations_Aggregator_WP($config);

    $url = 'http://localhost/TestPress4/wp-json/ecovillages/v1/get/index/Canada';

    $options['api_key'] = 'JD%2js9#dflj';

    $json = Murmurations_API::getIndexJson($url,array(),$options);

    //echo llog($json,"Index JSON");

    $index = json_decode($json,true);

    $node = $index['data'][0];

    $profile_url = $node['profile_url'];

    $json = Murmurations_API::getNodeJson($profile_url,$options);

    //echo llog($json,"Node JSON");

    $node = new Murmurations_Node($ag->schema,$ag->field_map,$ag->settings);

    $build_result = $node->buildFromJson($json);

    //echo llog($node,"Node after building from JSON");

    $id = $node->save();

    echo llog($id,"ID after saving post");

    $profile_url = $node->data['profile_url'];

    $db_node = new Murmurations_Node($ag->schema,$ag->field_map,$ag->settings);

    $post = $db_node->getPostFromProfileUrl($profile_url);

    echo llog($post,"WP Post loaded");

    $db_node->buildFromWPPost($post);

    echo llog($db_node,"Node after build from WP post");

  }


  public static function getIndexJson(){

    $url = 'http://localhost/TestPress4/wp-json/ecovillages/v1/get/index/Canada';

    $options['api_key'] = 'JD%2js9#dflj';

    $json = Murmurations_API::getIndexJson($url,array(),$options);

    return json_decode($json,true);

  }

  public static function getNodeJson(){

    $url = 'http://localhost/TestPress4/wp-json/ecovillages/v1/get/project/cohabitat-quebec';

    $options['api_key'] = 'JD%2js9#dflj';

    $json = Murmurations_API::getNodeJson($url,$options);

    return json_decode($json,true);

  }

  public static function keyedApiRequest(){
    $url = 'http://localhost/TestPress4/wp-json/ecovillages/v1/get/project/selba';

    $user = 'JD%2js9#dflj';
    $pass = null;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    $headers = array();
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
      function($curl, $header) use (&$headers){
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2)
          return $len;

        $headers[strtolower(trim($header[0]))][] = trim($header[1]);

        return $len;
      }
    );
    $result = curl_exec($ch);
    return array($result,print_r($headers,true));

  }

  public static function indexRequest(){
    //$url = 'https://index.murmurations.tech/v1/nodes';
    $url = 'http://localhost/TestPress4/wp-json/ecovillages/v1/get/index/Canada';

    $query = array();

    $fields_string = http_build_query($query);

    $ch = curl_init();

    $user = 'JD%2js9#dflj';
    $pass = null;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $pass);

    curl_setopt($ch,CURLOPT_URL, $url);
    //curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_FAILONERROR, true);

    $result = curl_exec($ch);

    if($result === false){
      echo "No result returned from cURL request to index. cURL error:".curl_error($ch);
    }

    echo "<pre>".print_r(json_decode($result,true),true);

  }

}




?>
