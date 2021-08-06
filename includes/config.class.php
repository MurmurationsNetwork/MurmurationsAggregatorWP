<?php
namespace Murmurations\Aggregator;

/*
* Config
*/

class Config {

	public static $config;

	public static function get( $var = null ) {
		if ( $var ) {
			return Settings::get( $var );
		} else {
			return Settings::get();
		}
	}
}
