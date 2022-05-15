<?php
/**
 * Field class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class for fields
 */
class Field {

	/**
	 * Compare the provenance of two versions of a field's data
	 *
	 * @param  string $primary_url node's primary_url.
	 * @param  array  $a node a.
	 * @param  array  $b node b.
	 * @return boolean true if value B should be used over value A, false otherwise
	 */
	public static function compare_provenance( $primary_url, $a, $b ) {

		// Check profile URL authority. If a profile is stored beneath the primary URL, it is more authoritative than a profile that is not.
		$a['is_authoritative_url'] = ( strpos( $a['profile_url'], $primary_url ) !== false );
		$b['is_authoritative_url'] = ( strpos( $b['profile_url'], $primary_url ) !== false );

		if ( $a['is_authoritative_url'] !== $b['is_authoritative_url'] ) {
			return $b['is_authoritative_url'];
		}

		/*
		TODO: Add intermediate authority check, against semi-authoritative 3rd party network domains
		*/

		/*
		TODO: Add authority checks based on rating of data sources
		*/

		// If there is not a more authoritative profile, use the newer one.

		return ( $a['last_updated'] < $b['last_updated'] );

	}
}
