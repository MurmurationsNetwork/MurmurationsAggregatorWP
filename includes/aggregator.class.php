<?php
namespace Murmurations\Aggregator;

class Aggregator {

	public $notices  = array();
	public $nodes    = array();
	public $config   = array();
	public $settings = array();

	public function __construct( $config = null ) {

		$default_config = array(
			'plugin_name'           => 'Murmurations Aggregator',
			'node_name'             => 'Murmurations Node',
			'node_name_plural'      => 'Murmurations Nodes',
			'node_slug'             => 'murmurations-node',
			'plugin_slug'           => 'murmurations',
			'api_route'             => 'murmurations-aggregator/v1',
			'feed_storage_path'     => MURMAG_ROOT_PATH . 'feeds/feeds.json',
			'schema_file'           => MURMAG_ROOT_PATH . 'schemas/default.json',
			'field_map_file'        => MURMAG_ROOT_PATH . 'schemas/field_map.json',
			'css_directory'         => MURMAG_ROOT_URL . 'css/',
			'template_directory'    => MURMAG_ROOT_PATH . 'templates/',
			'meta_prefix'           => 'murmurations_',
			'node_single_url_field' => false,
			'node_single'           => true,
			'enable_feeds'          => false,
			'log_file'              => MURMAG_ROOT_PATH . 'logs/murmurations_aggregator.log',
			'log_append'            => false,
		);

		$this->config = wp_parse_args( $config, $default_config );

		$this->config = apply_filters( 'murmurations-aggregator-config', $this->config );

		$this->load_includes();

		Config::$config = $this->config;

    Settings::load();

		$this->load_schema();
		$this->load_field_map();
		$this->register_hooks();

		if ( Settings::get('enable_feeds') === 'true' ) {
			Feeds::$wpagg = $this;
			Feeds::init();
		}

    /* Temporary arrangement... */
    if( is_admin() ){
      Admin::$wpagg = $this;
    }


	}



	public function load_schema() {
		if ( file_exists( $this->config['schema_file'] ) ) {
			$schema_json  = file_get_contents( $this->config['schema_file'] );
			$this->schema = json_decode( $schema_json, true );
		} else {
			$this->error( 'Schema file not found: ' . $this->config['schema_file'], 'fatal' );
		}
	}

	public function load_field_map() {
		if ( file_exists( $this->config['field_map_file'] ) ) {
			$map_json        = file_get_contents( $this->config['field_map_file'] );
			$this->field_map = json_decode( $map_json, true );
		} else {
			$this->error( 'Field map file not found: ' . $this->config['field_map_file'], 'fatal' );
		}
	}

	public function activate() {

    $admin_fields = Settings::get_fields();

		foreach ( $admin_fields as $name => $field ) {
			if ( $field['default'] ) {
        Settings::set( $name, $field['default'] );
			}
		}

		Settings::save();

	}


	public function deactivate() {
		$timestamp = wp_next_scheduled( 'murmurations_node_update' );
		wp_unschedule_event( $timestamp, 'murmurations_node_update' );

		$timestamp = wp_next_scheduled( 'murmurations_feed_update' );
		wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
	}


	/* Load an overridable template file */
	public function load_template( $template, $data ) {
		if ( file_exists( get_stylesheet_directory() . '/murmurations-aggregator-templates/' . $template ) ) {
			ob_start();
			include get_stylesheet_directory() . '/murmurations-aggregator-templates/' . $template;
			$html = ob_get_clean();
		} elseif ( file_exists( $this->config['template_directory'] . $template ) ) {
			ob_start();
			include $this->config['template_directory'] . $template;
			$html = ob_get_clean();
		} else {
			exit( 'Missing template file: ' . $template );
		}
		return $html;
	}


	// WP's enqueues don't accommodate integrity and crossorigin attributes without trickery, so we're using an action hook
	public function queue_leaflet_scripts() {
		add_action( 'wp_head', array( $this, 'leaflet_scripts' ) );
	}

	public function leaflet_scripts() {
		?>
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
  integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
  integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
  crossorigin=""></script>
		<?php
	}

	public function error( $message, $type = 'notice' ) {
		Notices::set( $message, $type );
		if ( $type == 'fatal' ) {
			exit( $message );
		}
	}

	public function load_nodes( $args = null ) {

		$default_args = array(
			'post_type'      => 'murmurations_node',
			'posts_per_page' => -1,
		);

		$args = wp_parse_args( $args, $default_args );

		$posts = get_posts( $args );

		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $key => $post ) {
				$this->nodes[ $post->ID ] = new Node( $this->schema, $this->field_map );

				$this->nodes[ $post->ID ]->buildFromWPPost( $post );

			}
		} else {
			llog( 'No node posts found in load_nodes using args: ' . print_r( $args, true ) );
		}
	}

	public function delete_all_nodes() {
		$nodes = get_posts(
			array(
				'post_type'   => 'murmurations_node',
				'numberposts' => -1,
				'post_status' => 'any',
			)
		);
		$count = 0;
		foreach ( $nodes as $node ) {
			$result = wp_delete_post( $node->ID, true );
			if ( $result ) {
				$count++;
			}
		}
		Notices::set( "$count nodes deleted" );
		return $count;
	}

	public function update_nodes() {

		$settings = Settings::get();

		$filters = $settings['filters'];

		if ( is_array( $filters ) ) {
			foreach ( $filters as $key => $f ) {
				if ( in_array( $f['field'], Config::get('index_fields') ) ) {
					$index_filters[] = [$f['field'], $f['comparison'], $f['value']];
				}
			}
		} else {
			$filters = array();
		}

		$update_since = $settings['update_time'];

		if ( $settings['ignore_date'] != 'true' ) {
			$index_filters[] = array( 'updated', 'isGreaterThan', $update_since );
		}

		$query = array();
		foreach ( $index_filters as $filter ) {
			$query[ $filter[0] ] = $filter[2];
		}

    $all_index_nodes = array();



    foreach ($settings['indices'] as $index) {

  		$options = array();

  		if ( isset( $index['api_key'] ) ) {
  			$options['api_key'] = $index['api_key'];
  		}
  		if ( isset( $index['api_basic_auth_user'] ) ) {
  			$options['api_basic_auth_user'] = $index['api_basic_auth_user'];
  		}
  		if ( isset( $index['api_basic_auth_pass'] ) ) {
  			$options['api_basic_auth_pass'] = $index['api_basic_auth_pass'];
  		}

  		$index_nodes = API::getIndexJson( $index['url'], $query, $options );

  		$index_nodes = json_decode( $index_nodes, true );

  		$index_nodes = $index_nodes['data'];

      if ( ! $index_nodes ) {
        Notices::set( 'Could not connect to index: ' . $index['url'], 'error' );
      } else {
        Notices::set( 'Fetched node info from index at ' . $index['url'], 'success' );
        $all_index_nodes = array_merge( $all_index_nodes, $index_nodes );
      }


    }

    $index_nodes = $all_index_nodes;

		$failed_nodes  = array();
		$fetched_nodes = array();
		$matched_nodes = array();
		$saved_nodes   = array();

		$results = array(
			'nodes_from_index' => array(),
			'failed_nodes'     => array(),
			'fetched_nodes'    => array(),
			'matched_nodes'    => array(),
			'saved_nodes'      => array(),
		);

		foreach ( $index_nodes as $key => $data ) {

			$url = $data['profile_url'];

			$results['nodes_from_index'][] = $url;

			$options = array();

			if ( isset( $settings['use_api_key_for_nodes'] ) && isset( $settings['api_key'] ) ) {
				if ( $settings['use_api_key_for_nodes'] == 'true' ) {
					$options['api_key'] = $settings['api_key'];
				}
			}
			if ( isset( $settings['api_basic_auth_user'] ) ) {
				$options['api_basic_auth_user'] = $settings['api_basic_auth_user'];
			}
			if ( isset( $settings['api_basic_auth_pass'] ) ) {
				$options['api_basic_auth_pass'] = $settings['api_basic_auth_pass'];
			}

			$node_data = API::getNodeJson( $url, $options );

			if ( ! $node_data ) {
				$results['failed_nodes'][] = $url;
			} else {

				$results['fetched_nodes'][] = $url;

				$node = new Node( $this->schema, $this->field_map );

				$build_result = $node->buildFromJson( $node_data );

				if ( ! $build_result ) {
					Notices::set( $node->getErrorsText(), 'error' );
					$results['failed_nodes'][] = $url;
					break;
				}

				$matched = $node->checkFilters( $filters );

				if ( $matched == true ) {
					$results['matched_nodes'][] = $url;

					$result = $node->save();

					if ( $result ) {
						$results['saved_nodes'][] = $url;
					} else {
						Notices::set( 'Failed to save node: ' . $url, 'error' );
					}
				} else {
					if ( $settings['unmatching_local_nodes_action'] == 'delete' ) {
						$node->delete();
					} else {
						$node->deactivate();
					}
				}
			}
		}

		$message = 'Nodes updated. ' . count( $results['nodes_from_index'] ) . ' nodes fetched from index. ' . count( $results['failed_nodes'] ) . ' failed. ' . count( $results['fetched_nodes'] ) . ' nodes returned results. ' . count( $results['matched_nodes'] ) . ' nodes matched filters. ' . count( $results['saved_nodes'] ) . ' nodes saved. ';

		if ( count( $results['saved_nodes'] ) > 0 ) {
			$class = 'success';
		} else {
			$class = 'notice';
		}

		Notices::set( $message, $class );

    Settings::set( 'update_time', time() );
    Settings::save();

	}


	public function show_directory() {
		$this->load_nodes();

		$html = '<div id="murmurations-directory">';
		if ( count( $this->nodes ) < 1 ) {
			$html .= 'No records found';
		} else {
			foreach ( $this->nodes as $key => $node ) {
				$html .= $this->load_template( 'node_list_item.php', $node->data );
			}
		}

		$html .= '</div>';
		return $html;
	}

	public function show_map() {
		$this->load_nodes();

		/*
		Because of the cross-origin stuff, these don't fit WP's queue paradigm. In future, we should use this method, but for now loading scripts as HTML in the head via env
		$this->env->add_css(array(
		'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.css",
		'integrity' => "sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==",
		'crossorigin' => "");

		$this->env->add_script(array(
		'href'=>"https://unpkg.com/leaflet@1.5.1/dist/leaflet.js",
		'integrity' => "sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==",
		'crossorigin' => "");
		*/

		// This API recently changed (https://docs.mapbox.com/help/troubleshooting/migrate-legacy-static-tiles-api)

		$html = $this->leaflet_scripts();

		$map_origin = Settings::get('map_origin');
		$map_scale  = Settings::get('map_scale');

		$html .= '<div id="murmurations-map" class="murmurations-map"></div>' . "\n";
		$html .= '<script type="text/javascript">' . "\n";
		$html .= "var murmurations_map = L.map('murmurations-map').setView([" . $map_origin . "], $map_scale);\n";

		$html .= "L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
    tileSize: 512,
    maxZoom: 18,
    zoomOffset: -1,
    id: 'mapbox/streets-v11',
    accessToken: '" . Settings::get('mapbox_token') . "'
}).addTo(murmurations_map);\n";

		foreach ( $this->nodes as $key => $node ) {

			if ( is_numeric( $node->data['geolocation']['lat'] ) && is_numeric( $node->data['geolocation']['lon'] ) ) {

				$popup = trim( $this->load_template( 'map_node_popup.php', $node->data ) );

				$lat = $node->data['geolocation']['lat'];
				$lon = $node->data['geolocation']['lon'];

				$html .= 'var marker = L.marker([' . $lat . ', ' . $lon . "]).addTo(murmurations_map);\n";
				$html .= "marker.bindPopup(\"$popup\");\n";

			}
		}

		$html .= "</script>\n";

		return $html;
	}

	public function load_includes() {
		$include_path = plugin_dir_path( __FILE__ );
		require_once $include_path . 'api.class.php';
		require_once $include_path . 'node.class.php';
		require_once $include_path . 'admin.class.php';
		require_once $include_path . 'geocode.class.php';
		require_once $include_path . 'settings.class.php';
		require_once $include_path . 'notices.class.php';
		require_once $include_path . 'schema.class.php';
		require_once $include_path . 'config.class.php';
		require_once $include_path . 'logging.php';
		require_once $include_path . 'feeds.class.php';
	}

	public function register_hooks() {

		add_action( 'init', array( $this, 'register_cpts_and_taxes' ) );

		add_shortcode( $this->config['plugin_slug'] . '-directory', array( $this, 'show_directory' ) );
		add_shortcode( $this->config['plugin_slug'] . '-map', array( $this, 'show_map' ) );

    if( is_admin() ){
      add_action(
        'admin_menu',
        array(
          'Murmurations\Aggregator\Admin',
          'add_settings_page'
        )
      );
    }


		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		wp_enqueue_style( 'murmurations-agg-css', MURMAG_ROOT_URL . 'css/murmurations-aggregator.css' );

		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );

		add_action( 'murmurations_node_update', array( $this, 'update_nodes' ) );

	}

	public function rest_get_nodes( $req ) {

		$operator_map = array(
			'equals'        => '=',
			'doesNotEqual'  => '!=',
			'isGreaterThan' => '>',
			'isLessThan'    => '<',
			'isIn'          => 'IN',
			'includes'      => 'LIKE',
		);

		$args = array();

		$map = $this->field_map;

		if ( isset( $req['search'] ) ) {
			$args['s'] = $req['search'];
		}

		foreach ( $map as $field => $attribs ) {
			if ( $attribs['post_field'] ) {
				if ( isset( $req[ $field ] ) ) {
					$args[ $attribs['post_field'] ] = $req[ $field ];
				}
			}
		}

		if ( isset( $req['filters'] ) ) {
			if ( is_array( $req['filters'] ) ) {
				if ( count( $req['filters'] ) > 0 ) {
					$meta_query = array();
					foreach ( $req['filters'] as $filter ) {
						$meta_query[] = array(
							'key'     => $filter[0],
							'value'   => $filter[2],
							'compare' => $operator_map[ $filter[1] ],
						);
					}
					$args['meta_query'] = $meta_query;
				}
			}
		}

		$this->load_nodes( $args );
		$rest_nodes = array();
		foreach ( $this->nodes as $node ) {
			$rest_nodes[] = $node->data;
		}
		return rest_ensure_response( $rest_nodes );
	}

	public function register_api_routes() {

		$result = register_rest_route(
			$this->config['api_route'],
			'get/nodes',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'rest_get_nodes' ),
			)
		);

		if ( ! $result ) {
			echo 'Failed to register rest route';
			exit;
		}
	}

	public function register_cpts_and_taxes() {

		register_post_type(
			'murmurations_node',
			array(
				'labels'        => array(
					'name'          => $this->config['node_name_plural'],
					'singular_name' => $this->config['node_name'],
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-admin-site-alt',
				'show_in_menu'  => true, // 'admin.php?page=murmurations-aggregator-settings',
				'menu_position' => 21,
				'rewrite'       => array( 'slug' => $this->config['node_slug'] ),
			)
		);

	}
}
?>
