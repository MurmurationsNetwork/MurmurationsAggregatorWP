<?php
/**
 * Config class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Configuration class (backwards compatibility only)
 *
 * Replaced by consolidated Settings API
 */
class Config {

	public static $config;

	/**
	 * Get a configuration value, or all values
	 *
	 * @param  string $var The property name
	 * @return mixed  The property value
	 */
	public static function get( $var = null ) {
		if ( $var ) {
			return Settings::get( $var );
		} else {
			return Settings::get();
		}
	}
}
