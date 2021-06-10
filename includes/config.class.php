<?php
namespace Murmurations\Aggregator;
/*
* Geocode addresses and other location information
*/

class Config {

  public static $config;

  public static function get($var = null){
    if($var){
      if(isset(self::$config[$var])){
        return self::$config[$var];
      }else{
        return false;
      }
    } else {
      return self::$config;
    }
  }

  public static function set ( $var, $value ){
    self::$config[$var] = $value;
  }

}
