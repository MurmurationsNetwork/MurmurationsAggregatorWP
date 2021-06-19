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

}
