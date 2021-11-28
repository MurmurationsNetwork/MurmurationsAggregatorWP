<?php
/**
 * Schema class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Handle schemas
 */
class Schema {
	/**
	 * Holds the final local schema for a running instance
	 *
	 * @var array
	 */
	public static $schema;

	/**
	 * The schema fields (the 'properties' property of the JSON Schema object)
	 *
	 * @var array
	 */
	public static $fields;

	/**
	 * The field map for relating schema fields to DB fields
	 *
	 * @var array
	 */
	public static $field_map;

	/**
	 * Base URL for dereferencing schemas and fields
	 *
	 * @var string
	 */
	public static $library_base_uri = 'https://cdn.murmurations.network/';
	/**
	 * Path from library base URL to directory for field definition JSON files
	 *
	 * @var string
	 */
	public static $library_fields_path = 'fields/';

	/**
	 * Load the local schema from the options table.
	 *
	 * If a local schema is not found, generate from schema URL(s) defined in settings
	 */
	public static function load() {

		$local_schema = get_option( 'murmurations_aggregator_local_schema' );

		if ( ! $local_schema ) {
			llog( 'No local schema found in options. Fetching.' );
			if ( is_array( Settings::get( 'schemas' ) ) ) {
				$schemas_info = Settings::get( 'schemas' );
				$schemas      = array();
				foreach ( $schemas_info as $schema_info ) {
					llog( 'Fetching schema from ' . $schema_info['location'] );
					$schema    = self::fetch( $schema_info['location'] );
					$schema    = self::dereference( $schema );
					$schemas[] = $schema;
				}

				$local_schema = self::merge( $schemas );

				update_option( 'murmurations_aggregator_local_schema', $local_schema );

				Notices::set( 'New local schema saved' );

				self::$schema = $local_schema;
				self::$fields = $local_schema['properties'];

			} else {
				error( 'No local schema or input schemas found', 'warn' );
			}
		} else {
				self::$schema = $local_schema;
				self::$fields = $local_schema['properties'];
		}

	}

	/**
	 * Retrieve the local schema, or a field thereof
	 *
	 * @param  string $field the name of a field to get.
	 * @return array Field definition, or whole schema.
	 */
	public static function get( $field = null ) {
		if ( ! self::$schema ) {
			self::load();
		}
		if ( $field ) {
			if ( isset( self::$schema['properties'][ $field ] ) ) {
				return self::$schema['properties'][ $field ];
			} else {
				return false;
			}
		} else {
			return self::$schema;
		}
	}

	/**
	 * Get all the fields of the schema (properties object)
	 *
	 * @return array Array of all the fields.
	 */
	public static function get_fields() {
		if ( ! self::$schema ) {
			self::load();
		}

		return self::$fields;
	}


	/**
	 * Set one or all of the attributes of a schema field
	 *
	 * @param string $field The name of the field.
	 * @param mixed  $attrib_or_attribs The attribute name, or the array of attributes.
	 * @param mixed  $value The value of an attribute.
	 */
	public static function set( $field, $attrib_or_attribs, $value = null ) {
		if ( $value && is_string( $attrib_or_attribs ) ) {
			self::$schema[ $field ][ $attrib_or_attribs ] = $value;
		} else {
			self::$schema[ $field ] = $attrib_or_attribs;
		}
	}

	/**
	 * Get the field map as an array
	 */
	public static function get_field_map() {
		if ( ! self::$field_map ) {
			$field_map_url = Settings::get( 'field_map_url' );
			if ( $field_map_url ) {
				$field_map = self::fetch( $field_map_url );
			} else {
				Notices::set( 'No field map URL found' );
				llog( 'Missing field map URL' );
				return false;
			}
			self::$field_map = $field_map;
		}
		return self::$field_map;
	}

	/**
	 * Merge multiple schemas
	 *
	 * Later schemas take precedence -- properties of earlier schemas will be recursively
	 * replaced by properties of later schemas with the same name.
	 *
	 * @param array $schemas An array of schemas as PHP arrays.
	 */
	public static function merge( array $schemas ) {

		$merged_schema = array();

		foreach ( $schemas as $schema ) {
			$merged_schema = array_replace_recursive( $merged_schema, $schema );
		}

		return $merged_schema;

	}


	/**
	 * Dereference a schema
	 *
	 * Fetch any included schemas
	 * Dereference fields by fetching their content
	 *
	 * @param array $schema Schema as a PHP array.
	 * @return array Dereferenced schema as a PHP array.
	 */
	public static function dereference( $schema ) {

		llog( 'Dereferencing schema' );

		$output_schema = array();

		if ( isset( $schema['include'] ) ) {
			if ( is_array( $schema['include'] ) ) {
				foreach ( $schema['include'] as $include_url ) {
					$array = self::fetch( $include_url );
					if ( $array ) {
						$output_schema = array_replace_recursive( $output_schema, self::dereference( $array ) );
					} else {
						Notices::set( 'Could not fetch included schema from ' . $include_url, 'warning' );
						llog( 'Could not fetch included schema from ' . $include_url );
					}
				}
			}
			unset( $schema['include'] );
		}

		/* For now, this can only handle refs for fields that are in the library! */

		foreach ( $schema['properties'] as $key => $attribs ) {
			if ( isset( $attribs['$ref'] ) ) {

				$ref_parts  = explode( '/', $attribs['$ref'] );
				$field_file = array_pop( $ref_parts );
				$url        = self::$library_base_uri . 'fields/' . $field_file;

				$field_data = self::fetch( $url );

				unset( $attribs['$ref'] );

				$schema['properties'][ $key ] = array_replace_recursive( $field_data, $attribs );

			}

      if ( isset($schema['properties'][ $key ]['items']['properties']) ){

        $schema['properties'][ $key ]['items'] = self::dereference( $schema['properties'][ $key ]['items'] );

      }

		}

		$output_schema = array_replace_recursive( $output_schema, $schema );

		return $output_schema;

	}

	/**
	 * Fetch a schema from a remote (or local) URL
	 *
	 * @param string $url The schema URL.
	 */
	public static function fetch( $url ) {

		llog( 'In fetch(). Getting schema from ' . $url );

		global $wp;

		llog( home_url( $wp->request ), 'Current URL' );

		if ( home_url( $wp->request ) == $url ) {
			llog( 'Problematic recursion detected in Schema::fetch()' );
			return;
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Cache-Control: no-cache' ) );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );

		$result = curl_exec( $ch );

		if ( $result === false ) {
			Notices::set( 'Request to fetch schema from ' . $url . ' failed. cURL error: ' . curl_error( $ch ) );
			llog( 'Request to fetch schema from ' . $url . ' failed. cURL error: ' . curl_error( $ch ) );
		} else {
			$result_ar = json_decode( $result, true );
			if ( ! $result_ar ) {
				Notices::set( 'Could not parse JSON of included schema from ' . $url, 'warning' );
				llog( 'Failed to parse JSON of schema fetched from ' . $url );
				llog( $result, 'Fetched JSON' );
			} else {
				llog( 'Successfully fetched and parsed schema.' );
			}
			$result = $result_ar;
		}

		curl_close( $ch );

		return $result;

	}

}
