<?php
namespace Murmurations\Aggregator;

/*
* Geocode addresses and other location information
*/

class Settings {

	public static $settings;

	public static function get( $var = null ) {
		if ( $var ) {
			if ( isset( self::$settings[ $var ] ) ) {
				return self::$settings[ $var ];
			} else {
				return false;
			}
		} else {
			return self::$settings;
		}
	}

	public static function set( $var, $value ) {
		self::$settings[ $var ] = $value;
	}

  public static function load() {
    self::$settings = get_option( 'murmurations_aggregator_settings' );
  }

  public static function save() {
    return update_option( 'murmurations_aggregator_settings', self::$settings );
  }

  public static function get_schema_json(){
    return file_get_contents( MURMAG_ROOT_PATH . 'admin_fields_jschema.json' );
  }

  public static function get_schema_array(){
    return json_decode( self::get_schema_json(), true );
  }

  public static function get_fields(){
    $admin_schema = self::get_schema_array();

    return $admin_schema['properties'];
  }

}
