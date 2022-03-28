<?php
/**
 * Settings class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class for handling settings
 *
 * Settings are stored in an option. Values can originate as defaults from the admin
 * schema, as settings overrides from a hook (for example from a wrapper plugin
 * or theme), or from the admin form.
 */
class Settings {

	/**
	 * The settings are stored here once loaded
	 *
	 * @var array
	 */
	private static $settings;
	/**
	 * The admin schema
	 *
	 * @var array
	 */
	private static $schema;


	/**
	 * Get a setting value (or all settings)
	 *
	 * @param  string $var Optional setting variable name.
	 * @return mixed setting value or all settings.
	 */
	public static function get( $var = null ) {
		if ( $var ) {
			$field = self::get_field( $var );
			if ( $field['value'] ) {
				return $field['value'];
			} elseif ( isset( self::$settings[ $var ] ) ) {
				return apply_filters( 'murmurations_aggregator_get_setting', self::$settings[ $var ], $var );
			} else {
				return apply_filters( 'murmurations_aggregator_get_setting', false, $var );
			}
		} else {
			return apply_filters( 'murmurations_aggregator_get_settings', self::$settings );
		}
	}

	/**
	 * Set a setting value
	 *
	 * @param string $var   variable name.
	 * @param mixed  $value value.
	 */
	public static function set( $var, $value ) {
		$value                  = apply_filters( 'murmurations_aggregator_set_setting', $value, $var );
		self::$settings[ $var ] = $value;
	}


	/**
	 * Load settings from DB
	 */
	public static function load() {
		$settings = get_option( 'murmurations_aggregator_settings' );

		$schema_fields = self::get_fields();

		foreach ( $schema_fields as $field => $attribs ) {
			if ( $attribs['value'] ) {
				$settings[ $field ] = $attribs['value'];
			} elseif ( $attribs['default'] ) {
				if ( null === $settings[ $field ] || '' === $settings[ $field ] || ( false === $settings[ $field ] && 'boolean' !== $attribs['type'] ) ) {
					$settings[ $field ] = $attribs['default'];
				}
			}
		}

		self::$settings = apply_filters( 'murmurations_aggregator_load_settings', $settings );
	}


	/**
	 * Save the settings to DB
	 *
	 * This can be called after settings have been updated using set().
	 *
	 * @return boolean true on success, false on failure.
	 */
	public static function save() {
		$settings = apply_filters( 'murmurations_aggregator_save_settings', self::$settings );
		return update_option( 'murmurations_aggregator_settings', $settings );
	}

	/**
	 * Get the schema JSON from the JSON file
	 *
	 * @return string Schema JSON
	 */
	private static function get_schema_json() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( MURMAG_ROOT_PATH . 'admin_fields_jschema.json' );
	}

	/**
	 * Get the schema as an array
	 *
	 * @return array Schema array
	 */
	public static function get_schema() {
		if ( ! self::$schema ) {
			self::load_schema();
		}
		return self::$schema;
	}

	/**
	 * Load the schema from JSON file, and store to class variable
	 *
	 * @param  array $default_values possible default values to set, overriding schema defaults.
	 */
	public static function load_schema( $default_values = null ) {

		$settings_schema = json_decode( self::get_schema_json(), true );

		if ( is_array( $default_values ) ) {
			foreach ( $default_values as $field => $default ) {
				$settings_schema['properties'][ $field ]['default'] = $default;
			}
		}

		$settings_schema = apply_filters( 'murmurations_aggregator_load_settings_schema', $settings_schema );

		self::$schema = $settings_schema;
	}

	/**
	 * Get all the schema fields
	 *
	 * @return array schema fields.
	 */
	public static function get_fields() {
		$admin_schema = self::get_schema();
		return $admin_schema['properties'];
	}


	/**
	 * Get the attributes of a single field from the admin schema
	 *
	 * @param  string $field field name.
	 * @return array attributes of the field
	 */
	public static function get_field( $field ) {
		if ( ! self::$schema ) {
			self::load_schema();
		}
		return self::$schema['properties'][ $field ];
	}

}
