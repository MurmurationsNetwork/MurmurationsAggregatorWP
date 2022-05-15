<?php
/**
 * API class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class that handles communications with the Murmurations network
 */
class Network {

	// We'd rather use PHP's cURL here instead of relying on WP functions, for now.
	// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init
	// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
	// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_exec
	// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_error
	// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_getinfo

	/**
	 * Fetch a node profile as JSON
	 *
	 * @param  string $url The node's profile URL.
	 * @param  array  $options Options for the request.
	 * @return string/boolean The returned JSON or false on failure.
	 */
	public static function get_node_json( $url, $options = null ) {

		$ch = curl_init();

		if ( $options['api_basic_auth_user'] && $options['api_basic_auth_pass'] ) {
			$curl_upass = $options['api_basic_auth_user'] . ':' . $options['api_basic_auth_pass'];
			if ( $options['api_key'] ) {
				$url .= '?' . http_build_query( array( 'api_key' => $options['api_key'] ) );
			}
		} else {
			if ( $options['api_key'] ) {
				$curl_upass = $options['api_key'] . ':';
			}
		}

		if ( $curl_upass ) {
			curl_setopt( $ch, CURLOPT_USERPWD, $curl_upass );
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Murmurations-Aggregator' );

		$result = curl_exec( $ch );

		if ( false === $result ) {
			Notices::set( 'No result returned from cURL request to node. cURL error: ' . curl_error( $ch ) );
			return false;
		}

		return $result;
	}

	/**
	 * Fetch the JSON for an index
	 *
	 * @param  string $url The index URL.
	 * @param  array  $query An array of key => value pairs to pass to the index as query parameters.
	 * @param  array  $options Options for the request.
	 * @return string|boolean JSON result or false on failure.
	 */
	public static function get_index_json( $url, $query, $options = null ) {

		$ch = curl_init();

		// For handling data sources that are behind basic auth:
		// If basic auth information is set, add it to cURL request...
		if ( $options['api_basic_auth_user'] && $options['api_basic_auth_pass'] ) {
			$curl_upass = $options['api_basic_auth_user'] . ':' . $options['api_basic_auth_pass'];
			// And put the api key, if present, into the query (not recommended).
			if ( $options['api_key'] ) {
				$query['api_key'] = $options['api_key'];
			}
		} else {
			// Otherwise use the cURL basic auth parameters for the api key (recommended).
			if ( $options['api_key'] ) {
				$curl_upass = $options['api_key'] . ':';
			}
		}

		$fields_string = http_build_query( $query );

		if ( $curl_upass ) {
			curl_setopt( $ch, CURLOPT_USERPWD, $curl_upass );
		}

		llog( 'Making index request to ' . $url . ' with ' . $fields_string );

		curl_setopt( $ch, CURLOPT_URL, $url . '?' . $fields_string );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Murmurations-Aggregator' );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, true );

		$result = curl_exec( $ch );

		if ( false === $result ) {
			Notices::set( 'No result returned from cURL request to index. cURL error: ' . curl_error( $ch ) );
			llog( curl_getinfo( $ch ), 'Failed index request.' );
			return false;
		} else {
			return $result;
		}
	}
}
