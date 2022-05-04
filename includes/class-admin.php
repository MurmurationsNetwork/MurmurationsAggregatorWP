<?php
/**
 * Admin class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Class to handle all admin functionality
 */
class Admin {
	/**
	 * Initialize if this is an admin page
	 */
	public static function init() {

		self::register_things();

	}

	/**
	 * Method called by WP hook to show the aggregator admin
	 */
	public static function show_admin_settings_page() {

		if ( isset( $_POST['action'] ) ) {
			check_admin_referer( 'murmurations_ag_actions_form' );
			if ( 'update_murms_feed_items' === $_POST['action'] ) {
				Feeds::update_feeds();
			}
			if ( 'update_nodes' === $_POST['action'] ) {
				Aggregator::update_nodes();
			}
			if ( 'delete_all_nodes' === $_POST['action'] ) {
				Aggregator::delete_all_nodes();
			}
		}

		?>
<h1><?php Utils::e( Config::get( 'plugin_name' ) . ' Settings' ); ?></h1>

<div id="murmagg-admin-help-links">
	<a href="https://docs.murmurations.network/technical/wp-aggregator.html">Documentation</a> |
	<a href="https://murmurations.flarum.cloud/">Forum</a> |
	<a href="https://github.com/MurmurationsNetwork/MurmurationsAggregatorWP">GitHub</a> |
	<a href="https://murmurations.network">Murmurations</a>
</div>
		<?php

		if ( isset( $_POST['murmurations_ag'] ) ) {
			self::process_admin_form();
		}

		Utils::e( Notices::show() );

		$tabs = array(
			'general'       => 'Dashboard',
			'data_sources'  => 'Data Sources',
			'interface'     => 'Interface',
			'map_settings'  => 'Map',
			'feed_settings' => 'Feeds',
			'config'        => 'Advanced',
		);

		$tabs = apply_filters( 'murmurations_aggregator_admin_tabs', $tabs );

		$tab = 'general';

		if ( isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ) {
			$tab = Utils::input( 'tab', 'GET' );
		}

		?>

	<nav class="nav-tab-wrapper">
		<?php
		foreach ( $tabs as $slug => $title ) {
			if ( 'feed_settings' !== $slug || Settings::get( 'enable_feeds' ) === 'true' ) {
				$class = $slug === $tab ? 'nav-tab-active' : '';
				Utils::e( '<a href="?page=' . Config::get( 'plugin_slug' ) . '-settings&tab=' . $slug . '" class="nav-tab ' . $class . '">' . $title . ' </a>' );
			}
		}
		?>
	</nav>

	<div class="murmag-admin-form-group-container" id="murmag-admin-group-<?php Utils::e( $tab ); ?>">

		<?php

		if ( 'general' === $tab ) {

			?>
		<div id="murms-dashboard-stats">
		<b><?php Utils::e( 'Local ' . Settings::get( 'node_name_plural' ) ); ?>:</b>
			<?php
			$count = wp_count_posts( 'murmurations_node' );
			Utils::e( $count->publish . ' ' );
			if ( Settings::get( 'update_time' ) ) {
				Utils::e( '<b>Last updated:</b> ' . gmdate( 'Y-m-d G:i:s T', Settings::get( 'update_time' ) ) );

			}
			?>

		</div>
		<form method="POST">
			<?php
			wp_nonce_field( 'murmurations_ag_actions_form' );
			?>
		<button onclick="ajaxUpdateNodes()" type="button" class="murms-update murms-has-icon"><i class="murms-icon murms-icon-update"></i>Update nodes from the network</button>

			<?php
			if ( Settings::get( 'enable_feeds' ) === 'true' ) :
				?>

				<button type="submit" name="action" class="murms-update murms-has-icon" value="update_murms_feed_items"><i class="murms-icon murms-icon-update"></i>Update feeds</button>

				<?php
			endif;
			?>

			<button type="submit"  onclick="return confirm('Are you sure you would like to delete all locally stored nodes?')"  name="action" class="murms-delete murms-has-icon" value="delete_all_nodes"><i class="murms-icon murms-icon-delete"></i>Delete all stored nodes</button>
			<button onclick="viewLocalSchema()" type="button" class="murms-update">View local schema</button>

		</form>
		<textarea id="murmagg-admin-form-log-container" style="width:100%; height: 400px; display: none;"></textarea>
			<?php
		}

		self::show_rjsf_admin_form( $tab );

		?>

	</div>

		<?php

	}

	/**
	 * Set empty values for RJSF
	 *
	 * @param array $field_attribs attributes for the field.
	 * @return string the empty value for the field
	 */
	public static function rjsf_set_empty_value( $field_attribs ) {

		if ( isset( $field_attribs['default'] ) ) {
			$value = $field_attribs['default'];
		} elseif ( 'array' === $field_attribs['type'] ) {
			$value = array();
		} elseif ( 'string' === $field_attribs['type'] ) {
			$value = '';
		}

		return $value;

	}

	/**
	 * Pre-process current settings data to meet RJSF data type requirements
	 *
	 * @param array $schema the RJSF admin schema.
	 * @param  array $values current values.
	 * @return array processed values.
	 */
	public static function fix_rjsf_data_types( $schema, $values ) {

		$processed = array();

		foreach ( $schema['properties'] as $field => $attribs ) {

			if ( isset( $values[ $field ] ) ) {

				$value = $values[ $field ];

				if ( null === $value || '' === $value || ( false === $value && 'boolean' !== $attribs['type'] ) ) {

					$value = self::rjsf_set_empty_value( $attribs );

				}

				if ( is_array( $value ) ) {

					// If the type is 'object', it means we've already recursed into an array,
					// and the items are objects, so we can send the attribs as the schema
					// directly for the next recursion

					if( 'object' === $attribs['type'] ) {

						$value = self::fix_rjsf_data_types( $attribs, $value );

					} else {

						// Otherwise, we're at an array layer, and we need to make a very simple
						// schema that defines the (numeric) keys as fields, so the processor will
						// know what to do with it

						// Note that these eight lines of code took about six hours to get right.
						// So if you ever need to adjust them, give yourself a day :)

						$field_schema = array();
						foreach ($value as $key => $item_value) {
							$field_schema['properties'][ $key ] = $attribs['items'];
						}

						$value = self::fix_rjsf_data_types( $field_schema, $value );

					}
				} elseif ( 'boolean' === $attribs['type'] && is_string( $value ) ) {
					if ( 'true' === $value ) {
								$value = true;
					} else {
						$value = false;
					}
				} elseif ( 'integer' === $attribs['type'] && is_string( $value ) ) {
					$value = (int) $value;
				}
			} else {
				$value = self::rjsf_set_empty_value( $attribs, $value );
			}

			$processed[ $field ] = $value;

		}
		return $processed;
	}

	/**
	 * Show the RJSF form for an admin tab
	 *
	 * @param  string $group the field group to be shown.
	 */
	public static function show_rjsf_admin_form( $group = null ) {

		$raw_admin_schema = Settings::get_schema();

		$current_values = Settings::get();

		/* Preprocess for certain special fields */

		$admin_schema = array();

		foreach ( $raw_admin_schema['properties'] as $field => $attribs ) {

			if ( ! $group || ( $attribs['group'] === $group ) ) {

				if ( $attribs['value'] ) {
					$attribs['readOnly'] = true;
					$attribs['title']    = isset( $attribs['title'] ) ? $attribs['title'] . ' (read only)' : $field . ' (read only)';
				}

				if ( 'array' === $attribs['type'] && ! is_array( $current_values[ $field ] ) ) {
					$current_values[ $field ] = array();
				}

				/* Build filter field select from data schema */

				if ( 'filters' === $field ) {

					$enum       = array();
					$enum_names = array();

					foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
						$enum[]       = $schema_field;
						$enum_names[] = $schema_field_attribs['title'];
					}

					$attribs['items']['properties']['field']['enum']      = $enum;
					$attribs['items']['properties']['field']['enumNames'] = $enum_names;

				} elseif ( 'filter_fields' === $field ) {
					// Add fields to the options for front-end filters.

					$enum       = array();
					$enum_names = array();

					foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
						$enum[]       = $schema_field;
						$enum_names[] = $schema_field_attribs['title'];
					}

					if ( count( $enum ) > 0 ) {
						$attribs['items']['enum']      = $enum;
						$attribs['items']['enumNames'] = $enum_names;
					}
				}

				$admin_schema['properties'][ $field ] = $attribs;
			}
		}

		$current_values = self::fix_rjsf_data_types( $admin_schema, $current_values );

		$admin_schema = apply_filters( 'murmurations_aggregator_admin_schema', $admin_schema );

		$admin_schema_json = wp_json_encode( $admin_schema );

		$current_values_json = wp_json_encode( $current_values );

		$nonce_field = wp_nonce_field( 'murmurations_ag_admin_form', 'murmurations_ag_admin_form_nonce', true, false );

		?>
<div id="murmagg-admin-form-container">
	<div id="murmagg-admin-form-overlay"></div>
	<div id="murmagg-admin-form-notice"></div>
	<div id="murmagg-admin-form"></div>
</div>

<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>

<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/react-jsonschema-form/dist/react-jsonschema-form.js"></script>

<?php // phpcs:enable ?>

<script>

	const Form = JSONSchemaForm.default;
	const schema = <?php Utils::e( $admin_schema_json ); ?>;
	const uiSchema = {
	filters: {
		classNames: "murmag-filter-field"
	},

	schemas: {
		classNames: "murmag-schemas-field"
	},

	indices: {
		classNames: "murmag-indices-field"
	}	};

	const formData = <?php Utils::e( $current_values_json ); ?>;
	const log = (type) => console.log.bind(console, type);

	const saveButton = React.createElement(
		'button',
		{
			type:"submit",
			className : "button button-primary button-large"
		},
		'Save Settings'
	);
	const element = React.createElement(
		Form,
		{
			schema,
			uiSchema,
			formData,
			onChange: log("changed"),
			onSubmit: murmagAdminFormSubmit,
			onError: log("errors",formData)
		},
		saveButton
	)

	ReactDOM.render(element, document.getElementById("murmagg-admin-form"));

</script>
		<?php

	}

	/**
	 * Save the output from the RJSF admin form, submitted by XHR
	 */
	public static function ajax_save_settings() {

		llog( 'Saving settings' );

		check_ajax_referer( 'ajax_validation', 'nonce' );

		$data = Utils::input( 'formData', 'POST' );

		llog( $data, 'Settings data in ajax_save' );

		if ( Settings::get( 'node_update_interval' ) !== $data['node_update_interval'] ) {
			$new_interval = $data['node_update_interval'];
			$timestamp    = wp_next_scheduled( 'murmurations_node_update' );
			wp_unschedule_event( $timestamp, 'murmurations_node_update' );
			if ( 'manual' !== $new_interval ) {
				wp_schedule_event( time(), $new_interval, 'murmurations_node_update' );
			}
		}

		$parse_new_schemas = false;

		if ( Settings::get( 'schemas' ) !== $data['schemas'] ) {
			$parse_new_schemas = true;
		}

		if ( Settings::get( 'enable_feeds' ) === 'true' ) {
			if ( Settings::get( 'feed_update_interval' ) !== $data['feed_update_interval'] ) {
				$new_interval = $data['feed_update_interval'];
				$timestamp    = wp_next_scheduled( 'murmurations_feed_update' );
				wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
				if ( 'manual' !== $new_interval ) {
					wp_schedule_event( time(), $new_interval, 'murmurations_feed_update' );
				}
			}
		}

		$admin_fields = Settings::get_fields();

		foreach ( $admin_fields as $key => $f ) {
			if ( isset( $data[ $key ] ) ) {
				if ( 'empty_array' === $data[ $key ] || 'empty_object' === $data[ $key ] ) {
					$data[ $key ] = array();
				}
				if ( 'empty_string' === $data[ $key ] ) {
					$data[ $key ] = '';
				}
				Settings::set( $key, $data[ $key ] );
			}
		}

		Settings::save();

		if ( true === $parse_new_schemas ) {

			llog( 'New schema URLs found' );

			foreach ( $data['schemas'] as $schema_info ) {

				Schema::add( $schema_info['location'] );

			}

			Notices::set( 'New local schema saved', 'success' );

		}

		Notices::set( 'Settings saved', 'success' );

		$result = array(
			'data'     => $data,
			'status'   => 'success',
			'messages' => Notices::get(),
		);

		wp_send_json( $result );
	}

	/**
	 * Add the settings page callback
	 */
	public function add_settings_page() {

		$args = array(
			'page_title' => Config::get( 'plugin_name' ) . ' Settings',
			'menu_title' => Config::get( 'plugin_name' ),
			'capability' => 'manage_options',
			'menu_slug'  => Config::get( 'plugin_slug' ) . '-settings',
			'function'   => array( 'Murmurations\Aggregator\Admin', 'show_admin_settings_page' ),
			'icon'       => 'dashicons-admin-site-alt',
			'position'   => 20,
		);

		add_menu_page(
			$args['page_title'],
			$args['menu_title'],
			$args['capability'],
			$args['menu_slug'],
			$args['function'],
			$args['icon'],
			$args['position']
		);

	}
	/**
	 * Enqueue admin script, with nonce
	 */
	public static function enqueue_admin_script() {
		wp_register_script(
			'murmurmurations-aggregator-admin',
			MURMAG_ROOT_URL . '/js/admin.js',
			array( 'jquery' ),
			'1.0.0',
			false
		);
		wp_enqueue_script( 'murmurmurations-aggregator-admin' );
		wp_localize_script(
			'murmurmurations-aggregator-admin',
			'murmurmurations_aggregator_admin',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'ajaxnonce' => wp_create_nonce( 'ajax_validation' ),
			)
		);

	}

	/**
	 * Register actions
	 */
	public static function register_things() {

		add_action(
			'admin_menu',
			array(
				'Murmurations\Aggregator\Admin',
				'add_settings_page',
			)
		);

		add_action(
			'wp_ajax_save_settings',
			array(
				'Murmurations\Aggregator\Admin',
				'ajax_save_settings',
			)
		);

		add_action(
			'wp_ajax_update_node',
			array(
				'Murmurations\Aggregator\Aggregator',
				'ajax_update_node',
			)
		);

		add_action(
			'wp_ajax_get_index_nodes',
			array(
				'Murmurations\Aggregator\Aggregator',
				'ajax_get_index_nodes',
			)
		);

		add_action(
			'wp_ajax_set_update_time',
			array(
				'Murmurations\Aggregator\Aggregator',
				'ajax_set_update_time',
			)
		);

		add_action(
			'wp_ajax_get_local_schema',
			array(
				'Murmurations\Aggregator\Aggregator',
				'ajax_get_local_schema',
			)
		);

		add_action(
			'wp_ajax_save_node',
			array(
				'Murmurations\Aggregator\Admin',
				'ajax_save_node',
			)
		);

		add_action(
			'admin_enqueue_scripts',
			array(
				__CLASS__,
				'enqueue_admin_script',
			)
		);

		if ( Settings::get( 'enable_node_edit' ) === 'true' ) {
			add_action(
				'add_meta_boxes_murmurations_node',
				array(
					__CLASS__,
					'add_admin_node_edit_form',
				)
			);
		}
	}
	/**
	 * Add the edit form meta box
	 */
	public static function add_admin_node_edit_form() {
		add_meta_box(
			'murmurations-node-edit',
			__( 'Edit Node Data' ),
			array( __CLASS__, 'show_admin_node_edit_form' ),
			'murmurations_node',
			'normal',
			'default'
		);
	}
	/**
	 * Show the RJSF form for node editing
	 */
	public static function show_admin_node_edit_form() {

		self::show_rjsf_node_form();

	}


	/**
	 * Show the RJSF form for editing a node (experimental!)
	 */
	public static function show_rjsf_node_form() {

		$local_schema = Schema::get();

		unset(
			$local_schema['$schema'],
			$local_schema['id'],
			$local_schema['title'],
			$local_schema['description']
		);

		$node = new Node( (int) Utils::input( 'post', 'GET' ) );

		$current_values = $node->data;

		$local_schema['properties']['profile_url'] = array(
			'type'  => 'string',
			'title' => 'profile_url',
		);

		$node_schema_json = wp_json_encode( $local_schema );

		$current_values_json = wp_json_encode( $current_values );

		?>

	<div id="murmagg-admin-form-container">
		<div id="murmagg-admin-form-overlay"></div>
		<div id="murmagg-admin-form-notice"></div>
		<div id="murmagg-admin-form"></div>
	</div>
	<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
	<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
	<script src="https://unpkg.com/react-jsonschema-form/dist/react-jsonschema-form.js"></script>
	<?php // phpcs:enable ?>
	<script>

	const murmagNodeFormSubmit = (Form, e) => {

		formOverlay = document.getElementById('murmagg-admin-form-overlay');

		formOverlay.style.visibility = "visible";

		var data = {
			'action': 'save_node',
			'formData': Form.formData
		};

		jQuery.post(ajaxurl, data, function(response) {

			formOverlay.style.visibility = "hidden";
			var noticeContainer = document.getElementById('murmagg-admin-form-notice');

			noticeContainer.innerHTML = "";

			for (const message of response.messages){
				var notice = document.createElement("div");
				notice.innerHTML = '<p>'+message.message+'</p>';
				notice.className = "notice notice-"+message.type;
				noticeContainer.appendChild(notice);
			}
			noticeContainer.style.display = "block";
		});

	}

	const Form = JSONSchemaForm.default;

	const schema = <?php Utils::e( $node_schema_json ); ?>;

	const uiSchema = {
		filters: {
			classNames: "murmag-filter-field"
		},

		schemas: {
			classNames: "murmag-schemas-field"
		},

		indices: {
			classNames: "murmag-indices-field"
		}
	};


	const formData = <?php Utils::e( $current_values_json ); ?>;

	const log = (type) => console.log.bind(console, type);

	const saveButton = React.createElement(
		'button',
		{
			type:"submit",
			className : "button button-primary button-large"
		},
		'Save Node'
	);

	const element = React.createElement(
		Form,
		{
			schema,
			uiSchema,
			formData,
			onChange: log("changed"),
			onSubmit: murmagNodeFormSubmit,
			onError: log("errors")
		},
		saveButton
	)
	ReactDOM.render(element, document.getElementById("murmagg-admin-form"));
	</script>
		<?php

	}

	/**
	 * Save the output from the RJSF node form, submitted by XHR
	 */
	public static function ajax_save_node() {

		check_ajax_referer( 'ajax_validation', 'nonce' );

		llog( 'Saving node' );

		$data = Utils::input( 'formData', 'POST' );

		$node = new Node( $data );

		$result = $node->save();

		if ( $result ) {
			$status = 'success';
			Notices::set( 'Node ' . $result . ' saved', 'success' );
		} else {
			$status = 'fail';
			Notices::set( 'Node ' . $result . ' could not be saved', 'failure' );
		}

		$result = array(
			'data'     => $data,
			'status'   => $status,
			'messages' => Notices::get(),
		);

		wp_send_json( $result );
	}

}
?>
