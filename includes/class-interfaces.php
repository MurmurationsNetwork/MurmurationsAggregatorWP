<?php
/**
 * Murmurations Interfaces class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Main interfaces class
 */
class Interfaces {

	/**
	 * Initialize
	 *
	 * This method is called when the plugin is first loaded
	 */
	public static function init() {

		define( 'MRI_WIDGET_PATH', plugin_dir_path( __FILE__ ) . '../interfaces' );
		define( 'MRI_ASSET_MANIFEST', MRI_WIDGET_PATH . '/build/asset-manifest.json' );

		self::enqueues();

		add_shortcode(
			'murmurations_react_directory',
			function( $atts ) {
				$default_atts = array();
				$args         = shortcode_atts( $default_atts, $atts );

				$code = Interfaces::prepare();

				return $code . "<div id='murmurations-react-directory'></div>";
			}
		);

		add_shortcode(
			'murmurations_react_map',
			function( $atts ) {
				$default_atts = array();
				$args         = shortcode_atts( $default_atts, $atts );

				$code = Interfaces::prepare();

				return $code . "<div id='murmurations-react-map'></div>";
			}
		);

	}

	/**
	 * Outputs the HTML to initialize and configure an interface (called by shortcode methods)
	 */
	public static function prepare() {

		// Set the default settings.
		$settings_defaults = array(
			'api_url'                  => get_rest_url( null, 'murmurations-aggregator/v1/get/nodes' ),
			'map_origin'               => '52, -97.1384',
			'map_scale'                => 4,
			'map_allow_scroll_zoom'    => 'true',
			'nodes_per_page'           => 10,
			'api_node_format'          => 'JSON',
			'client_path_to_app'       => MURMAG_ROOT_URL . 'interfaces/',
			'filter_fields'            => array(),
			'filter_schema'            => array(),
			'show_filters'             => false,
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			'directory_display_schema' => json_decode( file_get_contents( MURMAG_ROOT_PATH . 'schemas/default_directory_display_schema.json' ) ),
			true,
		);

		// Get settings from the aggregator if available.
		if ( is_callable( array( '\Murmurations\Aggregator\Settings', 'get' ) ) ) {
			$agg_settings = \Murmurations\Aggregator\Settings::get();
			$settings     = wp_parse_args( $agg_settings, $settings_defaults );
			$data_schema  = \Murmurations\Aggregator\Schema::get();

			$settings['filter_schema']['properties'] = self::generate_filter_schema_fields( $settings['filter_fields'], $data_schema );

			if ( count( $settings['filter_fields'] ) > 0 ) {
				$settings['show_filters'] = true;
			}
		} else {
				$settings = $settings_defaults;
		}

		// Run filters, in case wrappers or other plugins are modifying settings.
		$settings = apply_filters( 'murmurations_interfaces_settings', $settings );

		ob_start();
		?>
	<script>

	var mriSettings = {}

	mriSettings.filterSchema = <?php echo wp_json_encode( $settings['filter_schema'] ); ?>;
	mriSettings.showFilters = <?php echo $settings['show_filters'] ? 'true' : 'false'; ?>;
	mriSettings.directoryDisplaySchema = <?php echo wp_json_encode( $settings['directory_display_schema'] ); ?>;
	mriSettings.filterUiSchema = {};
	mriSettings.apiUrl = "<?php echo esc_url( $settings['api_url'] ); ?>";
	mriSettings.apiNodeFormat = "<?php echo esc_html( $settings['api_node_format'] ); ?>";
	mriSettings.schemaUrl = "";
	mriSettings.formData = {};
	mriSettings.mapCenter = [<?php echo esc_html( $settings['map_origin'] ); ?>];
	mriSettings.mapZoom = <?php echo esc_html( $settings['map_scale'] ); ?>;
	mriSettings.mapAllowScrollZoom = <?php echo esc_html( $settings['map_allow_scroll_zoom'] ); ?>;
	mriSettings.clientPathToApp = "<?php echo esc_url( $settings['client_path_to_app'] ); ?>";
	mriSettings.nodesPerPage = "<?php echo esc_html( $settings['nodes_per_page'] ); ?>";

	window.wpReactSettings = mriSettings;

	</script>

		<?php
		return ob_get_clean();
	}

	/**
	 * Generate fields for client-side filters
	 *
	 * @param  array $filter_fields Array of fields that will be used in filters.
	 * @param  array $data_schema the local schema for node data.
	 * @return array the array of fields with attributes and enum values, in JSON-Schema compatible structure.
	 */
	public static function generate_filter_schema_fields( $filter_fields, $data_schema ) {
		$filter_schema_fields = array();

		$local_values = get_option( 'murmurations_aggregator_filter_options' );

		foreach ( $filter_fields as $field ) {
			if ( isset( $data_schema['properties'][ $field ] ) ) {
				$field_attribs                  = $data_schema['properties'][ $field ];
				$filter_schema_fields[ $field ] = array(
					'title'    => $field_attribs['title'],
					'type'     => 'boolean' === $field_attribs['type'] ? 'boolean' : 'string',
					'operator' => 'includes',
				);

				$enum_data = self::get_field_enums( $field, $field_attribs, $local_values );

				if ( $enum_data ) {
					$filter_schema_fields[ $field ]['enum']      = $enum_data[0];
					$filter_schema_fields[ $field ]['enumNames'] = $enum_data[1];
				}
			}
		}
		return $filter_schema_fields;
	}

	/**
	 * Assign the enum valuev for a field
	 *
	 * @param  string $field the name of the field.
	 * @param  array  $field_attribs the schema attributes of this field.
	 * @param  array  $local_values all the local meta values for filter fields, from the option value.
	 * @return mixed Array of arrays of enums and enum names if successful, false if no local values for this field exist.
	 */
	public static function get_field_enums( $field, $field_attribs, $local_values ) {
		$enum       = array();
		$enum_names = array();
		$enum_keys  = array();
		if ( isset( $local_values[ $field ] ) ) {

			$local_values[ $field ] = array_values( $local_values[ $field ] );

			if ( isset( $field_attribs['enum'] ) ) {
				foreach ( $field_attribs['enum'] as $key => $value ) {
					if ( in_array( $value, $local_values[ $field ], true ) ) {
						$enum_keys[] = $key;
					}
				}

				foreach ( $enum_keys as $key ) {
					$enum[ $key ] = $field_attribs['enum'][ $key ];
					if ( isset( $field_attribs['enumNames'] ) ) {
						$enum_names[ $key ] = $field_attribs['enumNames'][ $key ];
					}
				}
			} else {
				$enum = $local_values[ $field ];
			}

			// Arrays need to be reindexed, because otherwise json_encode turns them into
			// objects.
			return array( array_values( $enum ), array_values( $enum_names ) );

		} else {
			return false;
		}
	}

	/**
	 * Enqueue the react-generated JS and CSS pieces by parsing the asset-manifest
	 */
	public static function enqueues() {

		add_action(
			'init',
			function() {

				add_filter(
					'script_loader_tag',
					function( $tag, $handle ) {
						if ( ! preg_match( '/^mri-/', $handle ) ) {
							return $tag; }
						return str_replace( ' src', ' async defer src', $tag );
					},
					10,
					2
				);

				add_action(
					'wp_enqueue_scripts',
					function() {
					  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						$asset_manifest = json_decode( file_get_contents( MRI_ASSET_MANIFEST ), true )['files'];

						// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
						// Ignore warnings about missing version information (because React's chunk
						// approach takes care of this).

						if ( isset( $asset_manifest['main.css'] ) ) {
							wp_enqueue_style( 'mri', get_site_url() . $asset_manifest['main.css'] );
						}

						wp_enqueue_script( 'mri-runtime', get_site_url() . $asset_manifest['runtime-main.js'], array(), null, true );

						wp_enqueue_script( 'mri-main', get_site_url() . $asset_manifest['main.js'], array( 'mri-runtime' ), null, true );

						foreach ( $asset_manifest as $key => $value ) {
							if ( preg_match( '@static/js/(.*)\.chunk\.js@', $key, $matches ) ) {
								if ( $matches && is_array( $matches ) && count( $matches ) === 2 ) {
									$name = 'mri-' . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
									wp_enqueue_script( $name, get_site_url() . $value, array( 'mri-main' ), null, true );
								}
							}

							if ( preg_match( '@static/css/(.*)\.chunk\.css@', $key, $matches ) ) {
								if ( $matches && is_array( $matches ) && count( $matches ) === 2 ) {
									$name = 'mri-' . preg_replace( '/[^A-Za-z0-9_]/', '-', $matches[1] );
									wp_enqueue_style( $name, get_site_url() . $value, array( 'mri' ), null );
								}
							}
						}

						// Enqueue RJSF.
						wp_enqueue_script( 'react-json-schema-form', 'https://unpkg.com/@rjsf/core/dist/react-jsonschema-form.js', null, null, true );
						//phpcs:enable
					}
				);
			}
		);
	}
}
