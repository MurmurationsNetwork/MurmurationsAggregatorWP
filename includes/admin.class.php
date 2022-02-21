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
	 *
	 */
  public static function init(){

    self::register_things();



  }

	/**
	 * Method called by WP hook to show the aggregator admin
	 */
	public static function show_admin_settings_page() {

		if ( isset($_POST['action']) ) {
			check_admin_referer( 'murmurations_ag_actions_form' );
			if ( $_POST['action'] === 'update_murms_feed_items' ) {
				Feeds::update_feeds();
			}
			if ( $_POST['action'] === 'update_nodes' ) {
				Aggregator::update_nodes();
			}
			if ( $_POST['action'] === 'delete_all_nodes' ) {
				Aggregator::delete_all_nodes();
			}
		}

		?>
	 <h1><?php echo esc_html( Config::get( 'plugin_name' ) ); ?> Settings</h1>

		<?php

		if ( isset( $_POST['murmurations_ag'] ) ) {
			self::process_admin_form();
		}

		echo Notices::show();

		$tabs = array(
			'general'       => 'Dashboard',
			'data_sources'  => 'Data Sources',
			'node_settings' => 'Nodes',
			'filters' => 'Filters',
			'map_settings'  => 'Map',
			'feed_settings' => 'Feeds',
			'config'        => 'Advanced',
		);

		$tabs = apply_filters( 'murmurations_aggregator_admin_tabs', $tabs );

		$tab = 'general';

		if ( isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ) {
			$tab = $_GET['tab'];
		}

		?>

	<nav class="nav-tab-wrapper">
		<?php
		foreach ( $tabs as $slug => $title ) {
			$class = $slug == $tab ? 'nav-tab-active' : '';
			echo '<a href="?page=' . Config::get( 'plugin_slug' ) . '-settings&tab=' . $slug . '" class="nav-tab ' . $class . '">' . $title . ' </a>';

		}
		?>
	</nav>

	<div class="murmag-admin-form-group-container" id="murmag-admin-group-<?php echo $tab; ?>">

		<?php

    if( 'general' === $tab ){

      ?>
      <div id="murms-dashboard-stats">
        <b>Local <?php echo Settings::get( 'node_name_plural' ); ?>:</b> <?php
        $count = wp_count_posts('murmurations_node');
        echo $count->publish . " ";
        if( Settings::get('update_time') ){
          echo "<b>Last updated:</b> ". date('Y-m-d G:i:s T', Settings::get('update_time'));

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
      <textarea id="murmagg-admin-form-log-container" style="width:100%; height: 400px; visibility: hidden;"></textarea>
      <?php

    } else {
      self::show_rjsf_admin_form( $tab );
    }


		?>

	</div>

		<?php

	}
	/**
	 * Pre-process current settings data to meet RJSF data type requirements
	 *
	 * @param array $schema the RJSF admin schema.
	 * @param  array $values current values.
	 * @return array processed values.
	 */
	public static function fix_rjsf_data_types( $schema, $values ) {
		foreach ( $schema['properties'] as $field => $attribs ) {

			// Make sure there's something there...
			/*
			if ( ! isset( $values[ $field ] ) ) {
        if ( 'string' === $attribs['type'] ){
          $values[ $field ] = '';
        } else {
          $values[ $field ] = null;
        }
			}
      */
     if(isset($values[ $field ])){

  			$value = $values[ $field ];

  			// Aggressively set default values.
  			if ( $attribs['default'] ) {
  				if ( $value === null || $value === '' || ( $value === false && $attribs['type'] !== 'boolean' ) ) {
  					$value = $attribs['default'];
  				}
  			}

  			if ( is_array( $value ) ) {
  				foreach ( $value as $key => $item ) {
  					// Sometimes array fields have their own "properties" property, and sometimes they don't.
  					if ( ! isset( $attribs['items']['properties'] ) ) {
  						$attribs['items']['properties'] = array( $attribs['items'] );
  					}
  					$value[ $key ] = self::fix_rjsf_data_types( $attribs['items'], $item );
  				}
  			} elseif ( $attribs['type'] === 'boolean' && is_string( $value ) ) {
  				if ( $value === 'true' ) {
  					$value = true;
  				} else {
  					$value = false;
  				}
  			} elseif ( $attribs['type'] === 'integer' && is_string( $value ) ) {
  				$value = (int) $value;
  			}

  			$values[ $field ] = $value;
      }
		}
		return $values;
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

				if ( $attribs['type'] === 'array' && ! is_array( $current_values[ $field ] ) ) {
					$current_values[ $field ] = array();
				}

				/* Build filter field select from data schema */

				if ( $field === 'filters' ) {

					$enum      = array();
					$enum_names = array();

					foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
						$enum[]      = $schema_field;
						$enum_names[] = $schema_field_attribs['title'];
					}

					$attribs['items']['properties']['field']['enum']      = $enum;
					$attribs['items']['properties']['field']['enumNames'] = $enum_names;

				} elseif ( $field === 'filter_fields' ) {
					// Add fields to the options for front-end filters.

					$enum      = array();
					$enum_names = array();

					foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
            $enum[]      = $schema_field;
            $enum_names[] = $schema_field_attribs['title'];
					}

          if (count($enum) > 0){
    					$attribs['items']['enum']      = $enum;
    					$attribs['items']['enumNames'] = $enum_names;
          }
				}

				$admin_schema['properties'][ $field ] = $attribs;
			}
		}

		$current_values = self::fix_rjsf_data_types( $admin_schema, $current_values );

		$admin_schema = apply_filters( 'murmurations_aggregator_admin_schema', $admin_schema );

		$admin_schema_json = json_encode( $admin_schema );

    $current_values_json = json_encode( $current_values );

		$nonce_field = wp_nonce_field( 'murmurations_ag_admin_form', 'murmurations_ag_admin_form_nonce', true, false );

		?>
<div id="murmagg-admin-form-container">
  <div id="murmagg-admin-form-overlay"></div>
  <div id="murmagg-admin-form-notice"></div>
  <div id="murmagg-admin-form"></div>
</div>



<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/react-jsonschema-form/dist/react-jsonschema-form.js"></script>

<script>

  const murmagAdminFormSubmit = (Form, e) => {

  	formOverlay = document.getElementById('murmagg-admin-form-overlay');

  	formOverlay.style.visibility = "visible";

    for (field in Form.formData){
      if(typeof(Form.formData[field]) == 'object'){
        if(Object.keys(Form.formData[field]).length === 0){
          Form.formData[field] = "empty_object";
        }
      }
      if(typeof(Form.formData[field]) == 'array'){
        if(Array.keys(Form.formData[field]).length === 0){
          Form.formData[field] = "empty_array";
        }
      }
    }

  	var data = {
  	  'action': 'save_settings',
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

  const schema = <?php echo $admin_schema_json; ?>;

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


  const formData = <?php echo $current_values_json; ?>;

  console.log("Current values", formData);

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
	  onError: log("errors")
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

		$data = $_POST['formData'];

    llog($data, "Settings data in ajax_save");

		if ( $data['node_update_interval'] != Settings::get( 'node_update_interval' ) ) {
			$new_interval = $data['node_update_interval'];
			$timestamp    = wp_next_scheduled( 'murmurations_node_update' );
			wp_unschedule_event( $timestamp, 'murmurations_node_update' );
			if ( $new_interval != 'manual' ) {
				wp_schedule_event( time(), $new_interval, 'murmurations_node_update' );
			}
		}

		$parse_new_schemas = false;

		if ( $data['schemas'] != Settings::get( 'schemas' ) ) {
			$parse_new_schemas = true;
		}

		if ( Settings::get( 'enable_feeds' ) === 'true' ) {
			if ( $data['feed_update_interval'] != Settings::get( 'feed_update_interval' ) ) {
				$new_interval = $data['feed_update_interval'];
				$timestamp    = wp_next_scheduled( 'murmurations_feed_update' );
				wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
				if ( $new_interval != 'manual' ) {
					wp_schedule_event( time(), $new_interval, 'murmurations_feed_update' );
				}
			}
		}

		$admin_fields = Settings::get_fields();

		foreach ( $admin_fields as $key => $f ) {
			if ( isset( $data[ $key ] ) ) {
        if($data[ $key ] === "empty_array" || $data[ $key ] === "empty_object"){
          $data[ $key ] = array();
        }
				Settings::set( $key, $data[ $key ] );
			}
		}

		Settings::save();

		if ( $parse_new_schemas === true ) {

			$schemas = array();
			llog( 'New schema URLs found' );

			foreach ( $data['schemas'] as $schema_info ) {

				$schema = Schema::fetch( $schema_info['location'] );

				$schema    = Schema::dereference( $schema );
				$schemas[] = $schema;
			}

			llog( 'Merging local schema out of ' . count( $schemas ) . ' fetched schema(s)' );

			$local_schema = Schema::merge( $schemas );

			llog( $local_schema, 'Local schema' );

			update_option( 'murmurations_aggregator_local_schema', $local_schema );

			Notices::set( 'New local schema saved', 'success' );

			Schema::load();

		}

		Notices::set( 'Settings saved', 'success' );

		$result = array(
			'data'     => $data,
			'dataStr'  => print_r( $data, true ),
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

  public static function enqueue_admin_script(){
    wp_enqueue_script( 'murmurations_aggregator_admin_js', plugin_dir_url( __FILE__ ) . '../js/admin.js');
  }


  public static function register_things(){

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
        'ajax_save_settings'
      )
    );

    add_action(
      'wp_ajax_update_node',
      array(
        'Murmurations\Aggregator\Aggregator',
        'ajax_update_node'
      )
    );

    add_action(
      'wp_ajax_get_index_nodes',
      array(
        'Murmurations\Aggregator\Aggregator',
        'ajax_get_index_nodes'
      )
    );

    add_action(
      'wp_ajax_set_update_time',
      array(
        'Murmurations\Aggregator\Aggregator',
        'ajax_set_update_time'
      )
    );

    add_action(
      'wp_ajax_get_local_schema',
      array(
        'Murmurations\Aggregator\Aggregator',
        'ajax_get_local_schema'
      )
    );

    add_action(
      'wp_ajax_save_node',
      array(
        'Murmurations\Aggregator\Admin',
        'ajax_save_node'
      )
    );

    add_action(
      'admin_enqueue_scripts',
      array(
        __CLASS__,
        'enqueue_admin_script'
      )
    );

    add_action(
      'add_meta_boxes_murmurations_node',
      array(
        __CLASS__,
        'add_admin_node_edit_form'
      )
    );
    
  }

  public static function add_admin_node_edit_form( $node_post ){
    add_meta_box(
      'murmurations-node-edit',
      __( 'Edit Node Data' ),
      array( __CLASS__, 'show_admin_node_edit_form' ),
      'murmurations_node',
      'normal',
      'default'
    );
  }

  public static function show_admin_node_edit_form(){

    self::show_rjsf_node_form();

  }


  	/**
  	 * Show the RJSF form for editing a node (experimental!)
  	 *
  	 */
  	public static function show_rjsf_node_form() {

  		$local_schema = Schema::get();

      unset(
        $local_schema['$schema'],
        $local_schema['id'],
        $local_schema['title'],
        $local_schema['description']
      );

      $node = new Node( (int) $_GET['post'] );

  		$current_values = $node->data;

      $local_schema['properties']['profile_url'] = array(
        'type' => 'string',
        'title' => 'profile_url'
      );

  		$node_schema_json = json_encode( $local_schema );

      $current_values_json = json_encode( $current_values );

  		?>
  <div id="murmagg-admin-form-container">
    <div id="murmagg-admin-form-overlay"></div>
    <div id="murmagg-admin-form-notice"></div>
    <div id="murmagg-admin-form"></div>
  </div>



  <script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
  <script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
  <script src="https://unpkg.com/react-jsonschema-form/dist/react-jsonschema-form.js"></script>

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

    const schema = <?php echo $node_schema_json; ?>;

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


    const formData = <?php echo $current_values_json; ?>;

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

      llog( 'Saving node' );

      $data = $_POST['formData'];

      $node = new Node( $data );

      $result = $node->save();

      if( $result ){
        $status = 'success';
        Notices::set( 'Node '.$result.' saved', 'success' );
      }else{
        $status = 'fail';
        Notices::set( 'Node '.$result.' could not be saved', 'failure' );
      }


      $result = array(
        'data'     => $data,
        'dataStr'  => print_r( $data, true ),
        'status'   => $status,
        'messages' => Notices::get()
      );

      wp_send_json( $result );
    }

}
?>
