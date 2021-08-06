<?php
namespace Murmurations\Aggregator;

class Settings {

	private static $settings;

  private static $schema;

	public static function get( $var = null ) {
		if ( $var ) {
      $field = self::get_field( $var );
      if( $field['value'] ){
        return $field['value'];
      } else if ( isset( self::$settings[ $var ] ) ) {
        return apply_filters( 'murmurations-aggregator-get-setting', self::$settings[ $var ], $var );
			} else {
				return apply_filters( 'murmurations-aggregator-get-setting', false, $var );
			}
		} else {
			return apply_filters( 'murmurations-aggregator-get-settings', self::$settings );
		}
	}

	public static function set( $var, $value ) {
    $value = apply_filters( 'murmurations-aggregator-set-setting', $value, $var );
		self::$settings[ $var ] = $value;
	}

  public static function load() {
    $settings = get_option( 'murmurations_aggregator_settings' );

    $schema_fields = self::get_fields();

    foreach ($schema_fields as $field => $attribs) {
      if( $attribs['value'] ){
        $settings[$field] = $attribs['value'];
      } else if( $attribs['default'] ){
        if( $settings[$field] === null || $settings[$field] === '' || ($settings[$field] === false && $attribs['type'] !== 'boolean')){
          $settings[$field] = $attribs['default'];
        }
      }
    }

    self::$settings = apply_filters( 'murmurations-aggregator-load-settings', $settings );
  }

  public static function save() {
    $settings = apply_filters( 'murmurations-aggregator-save-settings', self::$settings );
    return update_option( 'murmurations_aggregator_settings', $settings );
  }

  private static function get_schema_json(){
    return file_get_contents( MURMAG_ROOT_PATH . 'admin_fields_jschema.json' );
  }

  public static function get_schema(){
    if( ! self::$schema ){
      self::load_schema();
    }
    return self::$schema;
  }

  public static function load_schema( $default_values = null ){

    $settings_schema = json_decode( self::get_schema_json(), true );

    if( is_array( $default_values ) ){
      foreach ( $default_values as $field => $default ) {
        $settings_schema['properties'][$field]['default'] = $default;
      }
    }

    $settings_schema = apply_filters( 'murmurations-aggregator-load-settings-schema', $settings_schema );

    self::$schema = $settings_schema;
  }

  public static function get_fields(){
    $admin_schema = self::get_schema();
    return $admin_schema['properties'];
  }

  public static function get_field($field){
    if( ! self::$schema ){
      self::load_schema();
    }
    return self::$schema['properties'][$field];
  }

}
