<?php
namespace Murmurations\Aggregator;

/**
* Handle schemas
*
* This needs to be significantly updated, so that it can handle
*  - Merging of multiple schemas
*  - Resolving references
*/

class Schema {

	public static $schema;

  public static $fields;

  public static $library_base_uri = "https://cdn.murmurations.network/";
  public static $library_fields_path = "fields/";

  public static function load() {
    if ( file_exists( Config::get('schema_file') ) ) {
      $schema_json  = file_get_contents( Config::get('schema_file') );
      $schema = json_decode( $schema_json, true );
      if( $schema === null || ! isset( $schema[ 'properties' ] )){
        error( 'Invalid schema: ' . Config::get('schema_file'), 'fatal' );
      }else{
        self::$schema = $schema;
        self::$fields = $schema[ 'properties' ];
      }
    } else {
      error( 'Schema file not found: ' . Config::get('schema_file'), 'fatal' );
    }
  }

	public static function get( $field = null ) {
    if ( ! $schema ){
      self::load();
    }
		if ( $field ) {
			if ( isset( self::$schema[ 'properties' ][ $field ] ) ) {
				return self::$schema[ 'properties' ][ $field ];
			} else {
				return false;
			}
		} else {
			return self::$schema;
		}
	}

  public static function get_fields() {
    if ( ! $schema ){
      self::load();
    }

    return self::$fields;
  }

	public static function set( $field, $attrib, $value = null ) {
    if( $value && is_string($attrib) ){
      self::$schema[ $field ][ $attrib ] = $value;
    }else{
  		self::$config[ $field ] = $attrib;
    }
	}

  /**
  * Merge multiple schemas
  *
  * Later schemas take precedence -- properties of earlier schemas will be recursively
  * replaced by properties of later schemas with the same name
  *
  * @param array $schemas An array of schemas as PHP arrays.
  */

  public static function merge( array $schemas ) {

    $merged_schema = array();

    foreach ($schemas as $schema) {
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
  * @param array $schema Schema as a PHP array
  * @return array Dereferenced schema as a PHP array
  */

  public static function dereference( $schema ) {

    $output_schema = array();

    if ( isset( $schema['include'] ) ){
      if ( is_array( $schema['include'] ) ){
        foreach ( $schema['include'] as $include_url ) {
          $array = self::fetch( $include_url );
          if( $array ){
            $output_schema = array_replace_recursive( $output_schema, self::dereference( $array ) );
          } else {
            Notices::set( "Could not fetch included schema from " . $include_url, "warning" );
          }
        }
      }
      unset( $schema['include'] );
    }

    /* for now, this can only handle refs for fields that are in the library! */

    foreach ($schema['properties'] as $key => $attribs ) {
      if( isset( $attribs['$ref'] ) ) {

        $ref_parts = explode( "/", $attribs['$ref'] );
        $field_file = array_pop( $ref_parts );
        $url = self::$library_base_uri . "fields/" . $field_file;

        $field_data = self::fetch( $url );

        unset( $attribs['$ref'] );

        $schema['properties'][$key] = array_replace_recursive( $field_data, $attribs );

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
    $ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		$result = curl_exec( $ch );

		if ( $result === false ) {
			Notices::set( 'Request to fetch schema from '.$url.' failed. cURL error: ' . curl_error( $ch ) );
		} else {
      $result = json_decode( $result, true );
      if( ! $result ){
        Notices::set( "Could not parse JSON of included schema from " . $url, "warning" );
      }
    }

    curl_close($ch);

		return $result;

  }

}
