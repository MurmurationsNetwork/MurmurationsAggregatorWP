<?php
namespace Murmurations\Aggregator;

class Admin {

  public static $wpagg;

	public static function show_admin_settings_page() {

		if ( $_POST['action'] ) {
			check_admin_referer( 'murmurations_ag_actions_form' );
			if ( $_POST['action'] == 'update_murms_feed_items' ) {
				Feeds::update_feeds();
			}
			if ( $_POST['action'] == 'update_nodes' ) {
				self::$wpagg->update_nodes();
			}
			if ( $_POST['action'] == 'delete_all_nodes' ) {
				self::$wpagg->delete_all_nodes();
			}
		}

		?>
	 <h1><?php echo Config::get('plugin_name'); ?> Settings</h1>
	 <form method="POST">
		<?php
		wp_nonce_field( 'murmurations_ag_actions_form' );
		?>
	 <button type="submit" name="action" class="murms-update murms-has-icon" value="update_nodes"><i class="murms-icon murms-icon-update"></i>Update nodes</button>
		<?php
		if ( Settings::get( 'enable_feeds' ) === 'true' ) :
			?>
	   <button type="submit" name="action" class="murms-update murms-has-icon" value="update_murms_feed_items"><i class="murms-icon murms-icon-update"></i>Update feeds</button>
			<?php
	   endif;
		?>

	 <button type="submit" name="action" class="murms-delete murms-has-icon" value="delete_all_nodes"><i class="murms-icon murms-icon-delete"></i>Delete all stored nodes</button>

   </form>
		<?php

		if ( isset( $_POST['murmurations_ag'] ) ) {
			self::process_admin_form();
		}

		echo Notices::show();

    $tabs = [
      'general' => "General",
      'node_settings' => "Nodes",
      'map_settings' => "Map",
      'feed_settings' => "Feeds",
      'data_sources' => "Data Sources",
      'config' => "Advanced"
    ];

    $tabs = apply_filters( 'murmurations-aggregator-admin-tabs', $tabs );

    $tab = 'general';

    if(isset($_GET['tab']) && isset($tabs[$_GET['tab']])){
      $tab = $_GET['tab'];
    }

    ?>

    <nav class="nav-tab-wrapper">
      <?php
        foreach ($tabs as $slug => $title) {
          $class = $slug == $tab ? 'nav-tab-active' : "";
          echo '<a href="?page='.Config::get('plugin_slug').'-settings&tab='.$slug.'" class="nav-tab '. $class .'">'.$title.' </a>';

        }
       ?>
    </nav>

    <div class="murmag-admin-form-group-container" id="murmag-admin-group-<?= $tab ?>">

    <?php

    self::show_rjsf_admin_form($tab);

    ?>

    </div>

    <?php

	}

  public static function fix_rjsf_data_types( $schema, $values ){
    foreach ($schema['properties'] as $field => $attribs ) {
      $value = $values[$field];
      if(is_array($value)){
        foreach ($value as $key => $item) {
          $value[ $key ] = self::fix_rjsf_data_types( $attribs['items'], $item );
        }
      } else if( $attribs['type'] === 'boolean' && is_string($value)){
        if($value === 'true'){
          $value = true;
        }else{
          $value = false;
        }
      } else if( $attribs['type'] === 'integer' && is_string($value)){
        $value = (int) $value;
      }

      $values[$field] = $value;
    }
    return $values;
  }

  public static function show_rjsf_admin_form($group = null){

    $raw_admin_schema = Settings::get_schema_array();

    $current_values = Settings::get();

    /* Preprocess for certain special fields */

    $admin_schema = array();

    foreach ($raw_admin_schema['properties'] as $field => $attribs) {

      if( !$group || ($attribs['group'] == $group ) ) {

        if($attribs['type'] === 'array' && ! is_array( $current_values[$field] ) ){
          $current_values[$field] = array();
        }

        /* Load the template files list into enum options */

        if($field === 'directory_template'){
          $files = array_diff(
            scandir( Config::get( 'template_directory' ) ),
            array( '..', '.' )
          );

          $attribs['enum'] = array();

          foreach ( $files as $key => $fn ) {
            if ( substr( $fn, -4 ) == '.php' ) {
              $attribs['enum'][] = substr( $fn, 0, -4 );
            }
          }

        /* Build filter field select from data schema */

        } else if( $field === 'filters' ){

          $enum = array();
          $enumNames = array();

          foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
            $enum[] = $schema_field;
            $enumNames[] = $schema_field_attribs['title'];
          }

          $attribs['items']['properties']['field']['enum'] = $enum;
          $attribs['items']['properties']['field']['enumNames'] = $enumNames;

        }

        $admin_schema['properties'][$field] = $attribs;
      }
    }

    $current_values = self::fix_rjsf_data_types( $admin_schema, $current_values );

    $admin_schema_json = json_encode( $admin_schema );

    $current_values_json = json_encode( $current_values );

    $nonce_field = wp_nonce_field( 'murmurations_ag_admin_form' , 'murmurations_ag_admin_form_nonce', true, false);

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

    var data = {
      'action': 'save_settings',
      'formData': Form.formData
    };

    jQuery.post(ajaxurl, data, function(response) {
      console.log(response);
      formOverlay.style.visibility = "hidden";
      var notice = document.getElementById('murmagg-admin-form-notice');
      notice.style.display = "block";
      notice.innerHTML = '<p>'+response.message+'</p>';
      notice.className = "notice notice-"+response.status;
    });

  }

  const Form = JSONSchemaForm.default;

  const schema = <?= $admin_schema_json ?>;

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


  const formData = <?= $current_values_json ?>;

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

	public function load_admin_fields() {
		return json_decode( file_get_contents( dirname( __FILE__ ) . '/../admin_fields.json' ), true );
	}


	public function show_admin_form( $post_data = false ) {
		$current = Settings::get();

		$fields = json_decode( file_get_contents( dirname( __FILE__ ) . '/../admin_fields.json' ), true );

		$field_groups = array();

		// Reorganize into sections
		foreach ( $fields as $name => $field_info ) {
			$field_groups[ $field_info['group'] ][ $name ] = $field_info;
		}

		?>
	<form method="POST">
		<?php
		wp_nonce_field( 'murmurations_ag_admin_form' );

		foreach ( $field_groups as $group => $fields ) {
			$name = ucfirst( str_replace( '_', ' ', $group ) );
			?>
	  <div id="murms-admin-form-section-<?php echo $group; ?>" class="murms-admin-form-section">
		<h2><?php echo $name; ?></h2>
			<?php

			foreach ( $fields as $key => $f ) {
				$f['name']          = "murmurations_ag[$key]";
				$f['current_value'] = $current[ $key ];

				?>
		<div class="murmurations-ag-admin-field">
		  <label for="<?php echo $f['name']; ?>"><?php echo $f['title']; ?></label>
				<?php
				echo self::admin_field( $f );
				?>
		</div>

				<?php
			}

			echo '</div>';

		}

		?>
	<input type="submit" value="Save" class="button button-primary button-large">
</form>
		<?php

	}

	public static function admin_field( $f ) {

		// This is very rudimentary now. Possibly should be replaced with a field class
		if ( $f['inputAs'] == 'text' ) {

			$out = '<input type="text" class="" name="' . $f['name'] . '" id="' . $f['name'] . '" value="' . $f['current_value'] . '" />';

		} elseif ( $f['inputAs'] == 'checkbox' ) {

			if ( $f['current_value'] == 'true' ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}
			$out = '<input type="checkbox" class="checkbox" name="' . $f['name'] . '" id="' . $f['name'] . '" value="true" ' . $checked . '/>';

		} elseif ( $f['inputAs'] == 'select' ) {
			$options = $f['options'];
			$out     = '<select name="' . $f['name'] . '" id="' . $f['name'] . '">';
			$out    .= self::show_select_options( $options, $f['current_value'] );
			$out    .= '</select>';

		} elseif ( $f['inputAs'] == 'template_selector' ) {
			// This should be updated to find templates in the css directory
			// (It's overridable as is, but only by files of the same name)

			$files = array_diff( scandir( Config::get('template_directory') ), array( '..', '.' ) );

			$options = array();

			foreach ( $files as $key => $fn ) {
				if ( substr( $fn, -4 ) == '.php' ) {
					$name             = substr( $fn, 0, -4 );
					$options[ $name ] = $name;
				}
			}

			$out  = '<select name="' . $f['name'] . '" id="' . $f['name'] . '">';
			$out .= self::show_select_options( $options, $f['current_value'] );
			$out .= '</select>';

		} elseif ( $f['inputAs'] == 'multiple_array' ) {

			$filters = $f['current_value'];

			$out          = '<div class="murmurations_ag_filter_field_set">';
			$out         .= '<table><tr><th>Field</th><th>Match type</th><th>Value</th></tr>';
			$filter_count = 0;

			if ( is_array( $filters ) ) {
				foreach ( $filters as $key => $value ) {
					$out .= self::show_filter_field( $filter_count, $value );
					$filter_count++;
				}
			}

			while ( $filter_count < 5 ) {
				$out .= self::show_filter_field( $filter_count );
				$filter_count++;
			}

			$out .= '</table></div>';
		}
		return $out;
	}

	public static function show_filter_field( $id, $current_value = false ) {

		$keys = array( 'subject', 'predicate', 'object' );

		if ( ! $current_value ) {
			$current_value = array( '', '', '' );
		}

		$current_value = array_combine( $keys, $current_value );

		$subject_options = array( '' => '' );

		foreach ( Schema::get_fields() as $field => $attributes ) {
			$subject_options[ $field ] = $attributes['title'];
		}

		$match_options = array(
			''              => '',
			'includes'      => 'Includes',
			'equals'        => 'Equals',
			'isGreaterThan' => 'Is greater than',
			'isIn'          => 'Is in',
		);

		$out  = '<tr><td><select name="filters[' . $id . '][subject]">';
		$out .= self::show_select_options( $subject_options, $current_value['subject'] );
		$out .= '</select></td><td>';
		$out .= '<select name="filters[' . $id . '][predicate]">';
		$out .= self::show_select_options( $match_options, $current_value['predicate'] );
		$out .= '</select></td><td>';
		$out .= '<input type="text" class="" name="filters[' . $id . '][object]" value="' . $current_value['object'] . '" />';
		$out .= '</select></td></tr>';

		return $out;
	}

	public static function show_select_options( $options, $current = false ) {
		$out = '';
		foreach ( $options as $key => $value ) {
			if ( $current && $key == $current ) {
				$selected = 'selected';
			}
			$out     .= '<option ' . $selected . ' value="' . $key . '">' . $value . '</option>' . "\n";
			$selected = '';
		}
		return $out;
	}


	public static function process_admin_form() {

		$fields = self::load_admin_fields();

		$murm_post_data = $_POST['murmurations_ag'];

		check_admin_referer( 'murmurations_ag_admin_form', 'murmurations_ag_admin_form_nonce' );


		if ( is_array( $_POST['filters'] ) ) {
			foreach ( $_POST['filters'] as $key => $filter ) {
				if ( $filter['subject'] && $filter['predicate'] && $filter['object'] ) {
					$murm_post_data['filters'][] = array(
						$filter['subject'],
						$filter['predicate'],
						$filter['object'],
					);
				}
			}
		}

		if ( $murm_post_data['node_update_interval'] != Settings::get('node_update_interval') ) {
			$new_interval = $murm_post_data['node_update_interval'];
			$timestamp    = wp_next_scheduled( 'murmurations_node_update' );
			wp_unschedule_event( $timestamp, 'murmurations_node_update' );
			if ( $new_interval != 'manual' ) {
				wp_schedule_event( time(), $new_interval, 'murmurations_node_update' );
			}
		}

		if ( Settings::get('enable_feeds') === 'true' ) {
			if ( $murm_post_data['feed_update_interval'] != Settings::get('feed_update_interval') ) {
				$new_interval = $murm_post_data['feed_update_interval'];
				$timestamp    = wp_next_scheduled( 'murmurations_feed_update' );
				wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
				if ( $new_interval != 'manual' ) {
					wp_schedule_event( time(), $new_interval, 'murmurations_feed_update' );
				}
			}
		}

		foreach ( $fields as $key => $f ) {
			Settings::set( $key, $murm_post_data[ $key ] );
		}

		self::$wpagg->save_settings();

		Notices::set( 'Data saved', 'success' );

	}


  public static function ajax_save_settings(){

    llog("Saving settings");

    $data = $_POST['formData'];

    if ( $data['node_update_interval'] != Settings::get('node_update_interval') ) {
      $new_interval = $data['node_update_interval'];
      $timestamp    = wp_next_scheduled( 'murmurations_node_update' );
      wp_unschedule_event( $timestamp, 'murmurations_node_update' );
      if ( $new_interval != 'manual' ) {
        wp_schedule_event( time(), $new_interval, 'murmurations_node_update' );
      }
    }

    $schemas = array();

  if ( $data['schemas'] != Settings::get('schemas') ) {
      llog("New schema URLs found");
      foreach ( $data['schemas'] as $schema_info ) {
        llog($schema_info['location'], "Fetching schema");
        $schema = Schema::fetch($schema_info['location']);
        $schema = Schema::dereference($schema);
        $schemas[] = $schema;
      }

      $local_schema = Schema::merge($schemas);

      update_option( 'murmurations_aggregator_local_schema', $local_schema );

      Schema::load();

    }

    if ( Settings::get('enable_feeds') === 'true' ) {
      if ( $data['feed_update_interval'] != Settings::get('feed_update_interval') ) {
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
      if(isset($data[ $key ])){
        Settings::set( $key, $data[ $key ] );
      }
    }

    Settings::save();

    $result = array(
        'data' => $data,
        'dataStr' => print_r($data, true),
        'status' => 'success',
        'message' => 'Settings saved'
    );

    wp_send_json($result);
  }


	public function add_settings_page() {

		$args = array(
			'page_title' => Config::get('plugin_name') . ' Settings',
			'menu_title' => Config::get('plugin_name'),
			'capability' => 'manage_options',
			'menu_slug'  => Config::get('plugin_slug') . '-settings',
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

}
?>
