<?php
/**
 * Feeds class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Geocode addresses and other location information
 */
class Geocode {
	/**
	 * Hash of the location input, used as identifier for caching
	 *
	 * @var string Hash of the location input, used as identifier for caching
	 */
	private $location_hash;
	/**
	 * Array that holds results
	 *
	 * @var array
	 */
	private $geo;
	/**
	 * Errors
	 *
	 * @var array
	 */
	public $errors = array();
	/**
	 * Settings
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Constructor
	 *
	 * @param string $location The location to geocode.
	 */
	public function __construct( $location ) {

		$this->settings = array(
			'cache_dir' => plugin_dir_path( __FILE__ ) . 'geocode_cache/',
			'api_url'   => 'https://nominatim.openstreetmap.org/search',
		);

		$this->location_hash = $this->cacheHash( $location );
		$this->location      = $location;

	}

	/**
	 * Do the geocode lookup
	 *
	 * @return array coordinates
	 */
	public function get_coordinates() {
		$url         = $this->settings['api_url'];
		$cached_data = $this->load_cache_if_exists( $this->location_hash );

		if ( $cached_data ) {
			$this->geo = $cached_data;
		} else {

			$data = array(
				'q'      => $this->location,
				'format' => 'json',
			);

			$fields_string = http_build_query( $data );

			// phpcs:disable WordPress.WP.AlternativeFunctions

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url . '?' . $fields_string );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERAGENT, 'Murmurations' );

			$result = curl_exec( $ch );

			// phpcs:enable

			$data = json_decode( $result, true );

			if ( $this->safe_count( $data ) > 0 ) {
				$this->geo = $data[0]; // Get the first/best match.

			} else {
				$this->set_error( 'No matching location found' );
				return false;
			}

			$this->save_cache( $this->geo );
		}

		$this->lat = $this->geo['lat'];
		$this->lon = $this->geo['lon'];
		return $this->geo;

	}
	/**
	 * Set an error
	 *
	 * @param string $error the error message.
	 */
	public function set_error( $error ) {
		$this->errors[] = $error;
		llog( $error, 'Geocoding error' );
	}

	/**
	 * Generate the caching hash
	 *
	 * @param  string $location The location.
	 * @return string Hashed location
	 */
	public function cache_hash( $location ) {
		return md5( $location );
	}

	/**
	 * Save caches to files. Currently using hash as filename
	 *
	 * @param  array $data Location data.
	 * @return boolean
	 */
	public function save_cache( $data ) {
		// phpcs:ignore
		return file_put_contents( $this->settings['cache_dir'] . $this->location_hash, json_encode( $data ) );
	}

	/**
	 *  Load the cache
	 *
	 * @param  string $hash Hash of location.
	 * @return mixed array of geo data, false if cache doesn't exist
	 */
	public function load_cache_if_exists( $hash ) {
		if ( file_exists( $this->settings['cache_dir'] . $hash ) ) {
			// phpcs:ignore
			return json_decode( file_get_contents( $this->settings['cache_dir'] . $hash ), true );
		} else {
			return false;
		}
	}

	/**
	 *  Count an array that might not be an array
	 *
	 * @param  mixed $a the (possibly array) input to count.
	 * @return mixed integer if input is an array, otherwise false
	 */
	public function safe_count( $a ) {
		if ( is_array( $a ) ) {
			return count( $a );
		} else {
			return false;
		}
	}

}
