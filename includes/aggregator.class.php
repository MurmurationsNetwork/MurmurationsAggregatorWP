<?php
/**
 * Main aggregator controller class
 *
 * @package Murmurations Aggregator
 */

namespace Murmurations\Aggregator;

/**
 * Main aggregator controller class
 */
class Aggregator {
	/**
	 * Holds the array of nodes fetched from the network
	 */
	public $nodes = array();

	public function __construct() {

		$default_settings = array(
			'schemas'            => array( array( 'location' => MURMAG_ROOT_URL . 'schemas/default.json' ) ),
			'field_map_file'     => MURMAG_ROOT_URL . 'schemas/field_map.json',
			'css_directory'      => MURMAG_ROOT_URL . 'css/',
			'template_directory' => MURMAG_ROOT_PATH . 'templates/',
			'log_file'           => MURMAG_ROOT_PATH . 'logs/murmurations_aggregator.log',
		);

		$this->load_includes();

		Settings::load_schema( $default_settings );

		Settings::load();

		$this->register_hooks();

		if ( Settings::get( 'enable_feeds' ) == 'true' ) {
			Feeds::$wpagg = $this;
			Feeds::init();
		}

		/* Temporary arrangement... */
		if ( is_admin() ) {
			Admin::$wpagg = $this;
		}

	}

	/**
	 * Activate the plugin
	 */
	public function activate() {

		$admin_fields = Settings::get_fields();

		foreach ( $admin_fields as $name => $field ) {
			if ( $field['default'] ) {
				Settings::set( $name, $field['default'] );
			}
		}

		Settings::save();

	}

	/**
	 * Deactivate the plugin
	 */
	public function deactivate() {
		$timestamp = wp_next_scheduled( 'murmurations_node_update' );
		wp_unschedule_event( $timestamp, 'murmurations_node_update' );

		$timestamp = wp_next_scheduled( 'murmurations_feed_update' );
		wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
	}


	/**
	 * Load an (overridable) template file
	 *
	 * @param  string $template filename of template file
	 * @param  array  $data data that will be accessed in the template
	 * @return string HTML from template
	 */
	public static function load_template( $template, $data ) {
		$sources = array();

		if ( Settings::get( 'template_override_path' ) ) {
			$sources[] = Settings::get( 'template_override_path' );
		}

		$sources[] = get_stylesheet_directory() . '/murmurations-aggregator/';
		$sources[] = MURMAG_ROOT_PATH . 'templates/';

		foreach ( $sources as $dir ) {
			if ( file_exists( $dir . $template ) ) {
				ob_start();
				include $dir . $template;
				$html = ob_get_clean();
				break;
			}
		}

		if ( ! $html ) {
			error( 'Missing template file: ' . $template );
			return false;
		} else {
			return $html;
		}
	}

	/**
	 * Hook to queue Leaflet's scripts
	 *
	 * WP's enqueues don't accommodate integrity and crossorigin attributes without trickery, so we're using an action hook
	 */
	public function queue_leaflet_scripts() {
		add_action( 'wp_head', array( $this, 'leaflet_scripts' ) );
	}

	/**
	 * Write links to Leaflet js and css files
	 *
	 * @return string HTML for links
	 */
	public function leaflet_scripts() {
		return '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
  integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
  crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
  integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
  crossorigin=""></script>
';
	}

	/**
	 * Load saved nodes from the DB
	 *
	 * @param  array $args WP_Query compatible arguments
	 */
	public function load_nodes( $args = null ) {

		$default_args = array(
			'post_type'      => 'murmurations_node',
			'posts_per_page' => -1,
		);

		$args = wp_parse_args( $args, $default_args );

		$posts = get_posts( $args );

		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $key => $post ) {
				$this->nodes[ $post->ID ] = new Node( $post );
			}
		} else {
			llog( 'No node posts found in load_nodes using args: ' . print_r( $args, true ) );
		}
	}

	/**
	 * Delete all the saved nodes
	 *
	 * @return int the number of nodes deleted
	 */
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

	/**
	 * Fetch nodes from the network and store them locally
	 */
	public function update_nodes() {

		$settings = Settings::get();

		$filters = $settings['filters'];

		llog( $filters, 'Filters from settings in update_nodes' );

		$all_index_nodes = array();

		foreach ( $settings['indices'] as $index ) {

			$index_fields = explode( ',', $index['queryable_fields'] );

			$index_filters = array();

			if ( is_array( $filters ) ) {
				foreach ( $filters as $key => $f ) {
					if ( in_array( $f['field'], $index_fields ) ) {
						$index_filters[] = array( $f['field'], $f['comparison'], $f['value'] );
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

			llog( 'Fetching node JSON from ' . $url );

			$node_data = API::getNodeJson( $url, $options );

			if ( ! $node_data ) {
				$results['failed_nodes'][] = $url;
			} else {

				$results['fetched_nodes'][] = $url;

				$node = new Node( $node_data );

				if ( $node->hasErrors() ) {
					Notices::set( $node->getErrorsText(), 'error' );
					$results['failed_nodes'][] = $url;
					continue;
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

	/**
	 * Show the node directory (called by shortcode)
	 *
	 * @return string Directory HTML
	 */
	public function show_directory() {
		$this->load_nodes();

		$html = '<div id="murmurations-directory">';
		if ( count( $this->nodes ) < 1 ) {
			$html .= 'No records found';
		} else {
			foreach ( $this->nodes as $key => $node ) {
				$html .= self::load_template( 'node_list_item.php', $node->data );
			}
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Show the map of nodes (called by shortcode)
	 *
	 * @return string map HTML
	 */
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

		$map_origin = Settings::get( 'map_origin' );
		$map_scale  = Settings::get( 'map_scale' );

		$html .= '<div id="murmurations-map" class="murmurations-map"></div>' . "\n";
		$html .= '<script type="text/javascript">' . "\n";
		$html .= "var murmurations_map = L.map('murmurations-map').setView([" . $map_origin . "], $map_scale);\n";

		$html .= "L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
    tileSize: 512,
    maxZoom: 18,
    zoomOffset: -1,
    id: 'mapbox/streets-v11',
    accessToken: '" . Settings::get( 'mapbox_token' ) . "'
}).addTo(murmurations_map);\n";

		foreach ( $this->nodes as $key => $node ) {

			if ( is_numeric( $node->data['geolocation']['lat'] ) && is_numeric( $node->data['geolocation']['lon'] ) ) {

				$popup = trim( self::load_template( 'map_node_popup.php', $node->data ) );

				$lat = $node->data['geolocation']['lat'];
				$lon = $node->data['geolocation']['lon'];

				$html .= 'var marker = L.marker([' . $lat . ', ' . $lon . "]).addTo(murmurations_map);\n";
				$html .= "marker.bindPopup(\"$popup\");\n";

			}
		}

		$html .= "</script>\n";

		return $html;
	}

	/**
	 * Load the included files for the aggregator (note this should probably be replaced with an autoloader)
	 */
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

	/**
	 * Load the template for a single node, either from the template hierarchy if available, or from the default location in the plugin
	 *
	 * @param  string $template default template path from WP
	 * @return string Template path
	 */
	public function load_node_single_template( $template ) {

		if ( is_singular( 'murmurations_node' ) ) {
			$template = locate_template( 'single-murmurations_node.php', false );
			if ( ! $template ) {
				  $template = MURMAG_ROOT_PATH . 'templates/single-murmurations_node.php';
			}
		}

		return $template;
	}
	/**
	 * Load the template for the node archive page, either from the template hierarchy if available, or from the default location in the plugin
	 *
	 * @param  string $template default template path from WP
	 * @return string Template path
	 */
	public function load_node_archive_template( $template ) {
		if ( is_post_type_archive( 'murmurations_node' ) ) {
			$template = locate_template( 'archive-murmurations_node.php', false );
			if ( ! $template ) {
				$template = MURMAG_ROOT_PATH . 'templates/archive-murmurations_node.php';
			}
		}

		return $template;
	}

	/**
	 * Register the aggregator's hooks and enqueues and such
	 */
	public function register_hooks() {

		add_action( 'init', array( $this, 'register_cpts_and_taxes' ) );

		add_shortcode( Settings::get( 'plugin_slug' ) . '-directory', array( $this, 'show_directory' ) );
		add_shortcode( Settings::get( 'plugin_slug' ) . '-map', array( $this, 'show_map' ) );

		if ( is_admin() ) {
			add_action(
				'admin_menu',
				array(
					'Murmurations\Aggregator\Admin',
					'add_settings_page',
				)
			);
		}

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		wp_enqueue_style( 'murmurations-agg-css', MURMAG_ROOT_URL . 'css/murmurations-aggregator.css' );
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );

		add_action( 'murmurations_node_update', array( $this, 'update_nodes' ) );

		add_filter( 'single_template', array( $this, 'load_node_single_template' ) );

		add_filter( 'single-murmurations_node_template', array( $this, 'load_node_single_template' ) );
		add_filter( 'archive_template', array( $this, 'load_node_archive_template' ) );

	}

	/**
	 * REST API endpoint for accessing locally stored nodes
	 *
	 * @param  array $req query string parameters that came with the request
	 * @return string JSON of node data
	 */
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

		$map = Schema::get_field_map();

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
							'key'     => Settings::get( 'meta_prefix' ) . $filter[0],
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

	/**
	 * Register the API routes for accessing locally stored nodes
	 */
	public function register_api_routes() {

		$result = register_rest_route(
			Settings::get( 'api_route' ),
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

	/**
	 * Register custom post types and taxonomies
	 */
	public function register_cpts_and_taxes() {

		register_post_type(
			'murmurations_node',
			array(
				'labels'        => array(
					'name'          => Settings::get( 'node_name_plural' ),
					'singular_name' => Settings::get( 'node_name' ),
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-admin-site-alt',
				'show_in_menu'  => true, // 'admin.php?page=murmurations-aggregator-settings',
				'menu_position' => 21,
				'rewrite'       => array( 'slug' => Settings::get( 'node_slug' ) ),
			)
		);

	}
}
