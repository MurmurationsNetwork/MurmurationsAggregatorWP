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

}
