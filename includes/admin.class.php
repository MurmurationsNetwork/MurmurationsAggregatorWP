<?php
namespace Murmurations\Aggregator;

class Admin {

  public static $wpagg;

	public static function show_admin_settings_page() {
    if($_POST){
      echo "<pre>".print_r($_POST,true);

      exit();
    }

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
		if ( Config::get( 'enable_feeds' ) ) :
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

		/*
		TODO: Separate sections into their own JS-toggled tabs

		$admin_tabs = array(
		'network-settings' => 'Network Settings',
		'feeds' => 'Feeds'
		);

		$tab = $_GET['tab'];

		if($tab === null) $tab = 'network-settings';


		echo '<nav class="nav-tab-wrapper">';
		foreach ($admin_tabs as $key => $name) {
		echo "<a href=\"?page=murmurations-aggregator-settings&tab=$key\" class=\"nav-tab ";
		if($tab === $key) echo 'nav-tab-active';
		echo "\">$name</a>";
		}

		echo "</nav>";

		*/

		//self::show_admin_form( $murm_post_data );

    self::show_rjsf_admin_form();

	}

  public static function show_rjsf_admin_form(){

    //$admin_schema_json  = file_get_contents( MURMAG_ROOT_PATH . 'admin_fields_jschema.json' );
    //$schema = json_decode( $admin_schema_json, true );

    $admin_schema = Settings::get_schema_array();

    $current_values = Settings::get();

    foreach ($admin_schema['properties'] as $field => $attribs) {

      if($field === 'directory_template'){
        $files = array_diff( scandir( Config::get('template_directory') ), array( '..', '.' ) );

        $attribs['enum'] = array();

        foreach ( $files as $key => $fn ) {
          if ( substr( $fn, -4 ) == '.php' ) {
            $attribs['enum'][] = substr( $fn, 0, -4 );
          }
        }
      }else if($field === 'filters'){

        $enum = array();
        $enumNames = array();

        foreach ( Schema::get_fields() as $schema_field => $schema_field_attribs ) {
          $enum[] = $schema_field;
          $enumNames[] = $schema_field_attribs['title'];
        }

        $attribs['items']['properties']['field']['enum'] = $enum;
        $attribs['items']['properties']['field']['enumNames'] = $enumNames;

      }

      if( $attribs['type'] === 'boolean' && is_string($current_values[$field])){
        if($current_values[$field] === 'true'){
          $current_values[$field] = true;
        }else{
          $current_values[$field] = false;
        }
      }

      if( $attribs['type'] === 'integer' && is_string($current_values[$field])){
        $current_values[$field] = (int) $current_values[$field];
      }

      if($attribs['callback']){
        if( is_callable($attribs['callback']) ){
          $attribs = $attribs['callback']( $field, $attribs );
        }
      }

      $admin_schema['properties'][$field] = $attribs;

    }

    $admin_schema_json = json_encode($admin_schema);

    $current_values_json = json_encode($current_values);

    $nonce_field = wp_nonce_field( 'murmurations_ag_admin_form' , 'murmurations_ag_admin_form_nonce', true, false);

    ?>
<div id="murmagg-admin-form"></div>

<script src="https://unpkg.com/react@16/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@16/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/react-jsonschema-form/dist/react-jsonschema-form.js"></script>

<script>

  const murmagAdminFormSubmit = (Form, e) => {

    var data = {
      'action': 'save_settings',
      'formData': Form.formData
    };

    jQuery.post(ajaxurl, data, function(response) {
      console.log(response);
    });

  }

  const Form = JSONSchemaForm.default;

  const schema = <?= $admin_schema_json ?>;

  const uiSchema = {
    filters : {
      "ui:field" : "table"
    }
  }

  const formData = <?= $current_values_json ?>;

  const log = (type) => console.log.bind(console, type);

  const element = React.createElement(
    Form,
    {
      schema,
      formData,
      onChange: log("changed"),
      onSubmit: murmagAdminFormSubmit,
      onError: log("errors")
    }
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

		if ( Config::get('enable_feeds') ) {
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

    $data = $_POST['formData'];

    if ( $data['node_update_interval'] != Settings::get('node_update_interval') ) {
      $new_interval = $data['node_update_interval'];
      $timestamp    = wp_next_scheduled( 'murmurations_node_update' );
      wp_unschedule_event( $timestamp, 'murmurations_node_update' );
      if ( $new_interval != 'manual' ) {
        wp_schedule_event( time(), $new_interval, 'murmurations_node_update' );
      }
    }

    if ( Config::get('enable_feeds') ) {
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
