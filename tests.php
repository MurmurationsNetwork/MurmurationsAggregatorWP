<?php
error_reporting (E_ALL);
ini_set('dispay_errors', true);
define('WP_USE_THEMES', false);
$base = dirname(dirname(__FILE__));
require($base.'/../../wp-load.php');


echo "<pre>";
$result = MurmsAggregatorTests::keyedApiRequest();
print_r($result);
MurmsAggregatorTests::indexRequest();
echo "</pre>";

class MurmsAggregatorTests{

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
