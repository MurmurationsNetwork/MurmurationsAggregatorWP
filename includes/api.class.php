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
class API {
	/**
	 * Optional callback for logging
	 * TODO: Remove this and use global logging by default
	 */
	public static $logging_handler = false;

	/**
	 * Check if a node matches a filter condition
	 *
	 * @param  array $node The node to check.
	 * @param  array $condition Condition to check the node against.
	 * @return boolean Whether the node matched the condition.
	 */
	public static function checkNodeCondition( $node, $condition ) {

		list($subject, $predicate, $object) = $condition;

		if ( ! isset( $node[ $subject ] ) ) {
			return false;
		}

		switch ( $predicate ) {
			case 'equals':
				if ( $node[ $subject ] == $object ) {
					return true;
				}
				break;
			case 'isGreaterThan':
				if ( $node[ $subject ] > $object ) {
					return true;
				}
				break;
			case 'isLessThan':
				if ( $node[ $subject ] < $object ) {
					return true;
				}
				break;
			case 'isIn':
				if ( strpos( $object, $node[ $subject ] ) !== false ) {
					return true;
				}
				break;
			case 'includes':
				if ( strpos( $node[ $subject ], $object ) !== false ) {
					return true;
				}
				break;

			default:
				return false;
		}

	}

	/**
	 * Fetch a node profile as JSON
	 *
	 * @param  string $url The node's profile URL.
	 * @param  array  $options Options for the request.
	 * @return string/boolean The returned JSON or false on failure.
	 */
	public static function getNodeJson( $url, $options = null ) {

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

		if ( $result === false ) {
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
	public static function getIndexJson( $url, $query, $options = null ) {

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

		if ( $result === false ) {
			Notices::set( 'No result returned from cURL request to index. cURL error: ' . curl_error( $ch ) );
			llog( curl_getinfo( $ch ), 'Failed index request.' );
			return false;
		} else {
			return $result;
		}
	}
}
