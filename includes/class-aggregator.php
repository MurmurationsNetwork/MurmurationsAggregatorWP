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
	 * Initialize
	 */
	public static function init() {

		$default_settings = array(
			'schemas'            => array( array( 'location' => MURMAG_ROOT_URL . 'schemas/default.json' ) ),
			'field_map_file'     => MURMAG_ROOT_URL . 'schemas/field_map.json',
			'css_directory'      => MURMAG_ROOT_URL . 'css/',
			'template_directory' => MURMAG_ROOT_PATH . 'templates/',
			'log_file'           => MURMAG_ROOT_PATH . 'logs/murmurations_aggregator.log',
		);

		self::load_includes();

		Settings::load_schema( $default_settings );

		Settings::load();

		self::register_hooks();

		if ( Settings::get( 'enable_feeds' ) === 'true' ) {
			Feeds::init();
		}

		Interfaces::init();

		if ( is_admin() ) {
			Admin::init();
		}

	}

	/**
	 * Activate the plugin
	 */
	public static function activate() {

		$admin_fields = Settings::get_fields();

		$find = array(
			'{PLUGIN_DIR_PATH}',
			'{PLUGIN_DIR_URL}',
		);

		$replace = array(
			MURMAG_ROOT_PATH,
			MURMAG_ROOT_URL,
		);

		foreach ( $admin_fields as $name => $field ) {
			if ( $field['default'] ) {
				$default = str_replace( $find, $replace, $field['default'] );
				Settings::set( $name, $default );
			}
		}

		Settings::save();

	}

	/**
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'murmurations_node_update' );
		wp_unschedule_event( $timestamp, 'murmurations_node_update' );

		$timestamp = wp_next_scheduled( 'murmurations_feed_update' );
		wp_unschedule_event( $timestamp, 'murmurations_feed_update' );
	}

	/**
	 * Get the location of a template file.
	 *
	 * The sequence of priority is:
	 *  - Theme template location
	 *  - Location set in the 'template_override_path' setting var
	 *  - Default template from plugin directory
	 *
	 * @param  string $template the filename of the template.
	 * @return string|boolean full path of template file or false if template wasn't found anywhere
	 */
	public static function get_template_location( $template ) {

		$locations = array();

		$locations[] = locate_template( $template, false );

		if ( Settings::get( 'template_override_path' ) ) {
			$locations[] = Settings::get( 'template_override_path' ) . $template;
		}

		$locations[] = MURMAG_ROOT_PATH . 'templates/' . $template;

		foreach ( $locations as $location ) {
			if ( file_exists( $location ) ) {
				return $location;
			}
		}

		return false;

	}


	/**
	 * Load an (overridable) template file
	 *
	 * @param  string $template filename of template file.
	 * @param  array  $data data that will be accessed in the template.
	 * @return string HTML from template.
	 */
	public static function load_template( $template, $data ) {

		$location = self::get_template_location( $template );

		if ( $location ) {
			ob_start();
			include $location;
			$html = ob_get_clean();
			return $html;
		} else {
			error( 'Missing template file: ' . $template );
			return false;
		}
	}

	/**
	 * Hook to queue Leaflet's scripts
	 *
	 * WP's enqueues don't accommodate integrity and crossorigin attributes without trickery, so we're using an action hook
	 */
	public static function queue_leaflet_scripts() {
		add_action( 'wp_head', array( __CLASS__, 'leaflet_scripts' ) );
	}

	/**
	 * Write links to Leaflet js and css files
	 *
	 * @return string HTML for links
	 */
	public static function leaflet_scripts() {
		// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript,WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
		// Because of limitations with WP's handling of crossorigin and integrity checks for enqueued scripts we need to do this the crude way.
		return '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
   integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
   crossorigin=""/>
	 <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
   integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
   crossorigin=""></script>
	 ';
	 // phpcs:enable
	}

	/**
	 * Get saved nodes from the DB (static)
	 *
	 * @param  array $args WP_Query compatible arguments.
	 * @return array of Node objects.
	 */
	public static function get_nodes( $args = null ) {

		$default_args = array(
			'post_type'      => 'murmurations_node',
			'posts_per_page' => -1,
		);

		$nodes = array();

		$args = wp_parse_args( $args, $default_args );

		$posts = get_posts( $args );

		if ( count( $posts ) > 0 ) {
			foreach ( $posts as $key => $post ) {
				$nodes[ $post->ID ] = Node::build_from_wp_post( $post );
			}
		} else {
			llog( $args, 'No node posts found in get_nodes using args' );
		}

		return $nodes;

	}


	/**
	 * Delete all the saved nodes
	 *
	 * @return int the number of nodes deleted.
	 */
	public static function delete_all_nodes() {
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
	 * Get the local schema for client side inspection
	 */
	public static function ajax_get_local_schema() {

		$schema = Schema::get();

		if ( ! $schema ) {
			$status = 'failed';
		} else {
			$status = 'success';
		}

		wp_send_json(
			array(
				'status'   => $status,
				'schema'   => $schema,
				'messages' => Notices::get(),
			)
		);

	}



	/**
	 * Set the last node update time and filter value options after client-side node updates
	 */
	public static function ajax_wrap_up_nodes_update() {

		$update_time_result = self::set_update_time();

		$filter_options_result = Node::update_filter_options();

		if ( ! $filter_options_result ) {
			Notices::set("Failed to update filter options");
			llog("Failed to update filter options");
		} else {
			Notices::set("Updated filter options");
		}

		if ( ! $update_time_result ) {
			Notices::set("Failed to set update time");
			llog("Failed to set update time");
		} else {
			Notices::set("Set update time");
		}

		if ( ! ( $update_time_result && $filter_options_result ) ) {
			$status = 'failed';
		} else {
			$status = 'success';
		}

		wp_send_json(
			array(
				'status'   => $status,
				'messages' => Notices::get(),
			)
		);

	}


	/**
	 * Set the last node update time after client-side node updates
	 */
	public static function set_update_time() {

		Settings::set( 'update_time', time() );
		$result = Settings::save();

		if ( $result ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Get index nodes by AJAX
	 */
	public static function ajax_get_index_nodes() {
		$nodes = self::get_index_nodes();

		$result = array(
			'status'   => 'success',
			'messages' => Notices::get(),
			'nodes'    => $nodes,
		);

		if ( ! $nodes ) {
			$result['status'] = 'failure';
		}

		wp_send_json( $result );

	}

	/**
	 * Get node info from indices
	 *
	 * @return array Array of nodes fetched from indices.
	 */
	public static function get_index_nodes() {

		$settings = Settings::get();

		$all_index_nodes = array();

		foreach ( $settings['indices'] as $index_key => $index ) {

			if ( ( ! $index['disabled'] ) || ( 'false' === $index['disabled'] ) ) {

				$update_since = $settings['update_time'];

				$query = array();

				if ( 'true' !== $settings['ignore_date'] ) {
					$query['last_validated'] = $update_since;
				}

				if ( isset( $index['parameters'] ) ) {
					foreach ( $index['parameters'] as $pair ) {
						$query[ $pair['parameter'] ] = $pair['value'];
					}
				}

				$index_options = array();

				if ( isset( $index['api_key'] ) ) {
					$index_options['api_key'] = $index['api_key'];
				}
				if ( isset( $index['api_basic_auth_user'] ) ) {
					$index_options['api_basic_auth_user'] = $index['api_basic_auth_user'];
				}
				if ( isset( $index['api_basic_auth_pass'] ) ) {
					$index_options['api_basic_auth_pass'] = $index['api_basic_auth_pass'];
				}

				$index_nodes = Network::get_index_json( $index['url'], $query, $index_options );

				$index_nodes = json_decode( $index_nodes, true );

				if ( ! $index_nodes ) {
					Notices::set( 'Could not parse index JSON from: ' . $index['url'], 'error' );
					llog( $index_nodes, 'Could not parse index JSON' );
				} else {

						$index_nodes = $index_nodes['data'];

					foreach ( $index_nodes as $key => $node ) {
						$index_nodes[ $key ]['index_options'] = $index;
					}

					Notices::set( 'Fetched node info from index at ' . $index['url'], 'success' );
					$all_index_nodes = array_merge( $all_index_nodes, $index_nodes );
				}
			}
		}

		return $all_index_nodes;

	}

	/**
	 * Update node via ajax call
	 */
	public static function ajax_update_node() {

		check_ajax_referer( 'ajax_validation', 'nonce' );

		if ( isset( $_POST['node'] ) ) {
			$node = Utils::input( 'node' );

			$result = self::update_node( $node );

			$feedback = array(
				'status'   => 'success',
				'messages' => Notices::get(),
			);

			if ( ! $result ) {
				$feedback['status'] = 'failed';
			}

			wp_send_json( $feedback );

		} else {
			Notices::set( 'Ajax update node request without node data' );
			$feedback = array(
				'status'   => 'failed',
				'messages' => Notices::get(),
			);
			wp_send_json( $feedback );
			return false;
		}
	}

	/**
	 * Update locally stored node from the Network
	 *
	 * @param array $data profile data for the node.
	 */
	public static function update_node( $data ) {

		if ( ! isset( $data['last_updated'] ) && isset( $data['last_validated'] ) ) {
			$data['last_updated'] = $data['last_validated'];
		}

		$url = $data['profile_url'];

		$options = array();

		if ( isset( $data['index_options']['use_api_key_for_nodes'] ) && isset( $data['index_options']['api_key'] ) ) {
			if ( 'true' === $data['index_options']['use_api_key_for_nodes'] ) {
				$options['api_key'] = $data['index_options']['api_key'];
			}
		}
		if ( isset( $data['index_options']['api_basic_auth_user'] ) ) {
			$options['api_basic_auth_user'] = $data['index_options']['api_basic_auth_user'];
		}
		if ( isset( $data['index_options']['api_basic_auth_pass'] ) ) {
			$options['api_basic_auth_pass'] = $data['index_options']['api_basic_auth_pass'];
		}

		Notices::set( "Fetching node from $url" );

		$node_json = Network::get_node_json( $url, $options );

		if ( ! $node_json ) {
			error( "Could not fetch node from $url" );
			return false;
		} else {

			$node_array = json_decode( $node_json, true );

			if ( ! $node_array ) {

				error( 'Attempted to build node from invalid JSON. Could not parse.' );
				llog( $node_json, "Failed to parse node JSON from $url" );

				return false;

			} else {

				$provenance = array(
					'profile_url'  => $data['profile_url'],
					'last_updated' => $data['last_updated'],
					'index_url'    => $data['index_options']['url'],
				);

				// Make sure the profile URL is included in the node data.
				if ( ! isset( $node_array['profile_url'] ) ) {
					$node_array['profile_url'] = $data['profile_url'];
				}

				Notices::set( 'Fetched JSON' );

				$filters = Settings::get( 'filters' );

				if ( is_array( $filters ) ) {
					$matched = Node::check_filters( $node_array, $filters );
				} else {
					$matched = true;
				}

				if ( true === $matched ) {

					llog( 'Filters passed. Saving node.' );

					$result = Node::upsert( $node_array, $provenance );

					if ( Node::has_errors() ) {
						Notices::set( Node::get_errors_text(), 'error' );
						return false;
					}

					if ( ! $result ) {
						Notices::set( 'Failed to save node: ' . $url, 'error' );
					} else {
						Notices::set( 'Node successfully saved', 'success' );

						foreach ( $node_array['linked_schemas'] as $schema_name ) {
							llog( $schema_name, 'Adding schema' );
							Schema::add( Schema::name_to_url( $schema_name ) );
						}

						return $result;
					}
				} else {
					Notices::set( 'Node did not match filters: ' . $url, 'notice' );

				}
			}
		}

	}


	/**
	 * Fetch nodes from the network and store them locally
	 */
	public static function update_nodes() {

		$index_nodes = self::get_index_nodes();

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

			$results['nodes_from_index'][] = $data['profile_url'];

			$result = self::update_node( $data );

			if ( ! $result ) {
				$results['failed_nodes'][] = $data['profile_url'];
			} else {
				$results['saved_nodes'][] = $data['profile_url'];
			}
		}

		$message = 'Nodes updated. ' . count( $results['nodes_from_index'] ) . ' nodes fetched from index. ' . count( $results['failed_nodes'] ) . ' failed. ' . count( $results['fetched_nodes'] ) . ' nodes returned results. ' . count( $results['matched_nodes'] ) . ' nodes matched filters. ' . count( $results['saved_nodes'] ) . ' nodes saved. ';

		if ( count( $results['saved_nodes'] ) > 0 ) {
			$class = 'success';
		} else {
			$class = 'notice';
		}

		Notices::set( $message, $class );

		$update_time_result = self::set_update_time();

		$filter_options_result = Node::update_filter_options();

		if ( ! $filter_options_result ) {
			Notices::set("Failed to update filter options");
		} else {
			Notices::set("Updated filter options");
		}

		if ( ! $update_time_result ) {
			Notices::set("Failed to set update time");
		} else {
			Notices::set("Set update time");
		}

	}

	/**
	 * Show the node directory (called by shortcode)
	 *
	 * @return string Directory HTML
	 */
	public static function show_directory() {
		$nodes = self::get_nodes();

		$html = '<div id="murmurations-directory">';
		if ( count( $nodes ) < 1 ) {
			$html .= 'No records found';
		} else {
			foreach ( $nodes as $key => $node ) {
				$html .= self::load_template( 'node_list_item.php', $node );
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
	public static function show_map() {
		$nodes = self::get_nodes();

		/*
		This is a crude way to do this, done because cross origin things are not (yet)
		well handled in WP enqueues
		*/

		$html = self::leaflet_scripts();

		$map_origin = Settings::get( 'map_origin' );
		$map_scale  = Settings::get( 'map_scale' );

		$mapbox_key = Settings::get( 'mapbox_token' );

		$html .= '<div id="murmurations-map" class="murmurations-map"></div>' . "\n";
		$html .= '<script type="text/javascript">' . "\n";
		$html .= "var murmurations_map = L.map('murmurations-map').setView([" . $map_origin . "], $map_scale);\n";

		if ( $mapbox_key ) {
			$html .= "L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
	    	attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors, <a href=\"https://creativecommons.org/licenses/by-sa/2.0/\">CC-BY-SA</a>, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
	    	tileSize: 512,
	    	maxZoom: 18,
	    	zoomOffset: -1,
	    	id: 'mapbox/streets-v11',
	    	accessToken: '" . $mapbox_key . "'
			}).addTo(murmurations_map);\n";
		} else {
			$html .= "L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/\">OpenStreetMap</a> contributors',
				maxZoom: 18
			}).addTo(murmurations_map);\n";
		}

		foreach ( $nodes as $key => $node ) {

			$has_coords = false;

			if ( isset( $node['geolocation']['lat'] ) && isset( $node['geolocation']['lon'] ) ) {
				$lat        = $node['geolocation']['lat'];
				$lon        = $node['geolocation']['lon'];
				$has_coords = true;
			}

			if ( isset( $node['latitude'] ) && isset( $node['longitude'] ) ) {
				$lat        = $node['latitude'];
				$lon        = $node['longitude'];
				$has_coords = true;
			}

			if ( $has_coords ) {

				$popup = trim( self::load_template( 'map_node_popup.php', $node ) );

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
	public static function load_includes() {
		$include_path = plugin_dir_path( __FILE__ );
		require_once $include_path . 'class-network.php';
		require_once $include_path . 'class-node.php';
		require_once $include_path . 'class-field.php';
		require_once $include_path . 'class-admin.php';
		require_once $include_path . 'class-geocode.php';
		require_once $include_path . 'class-settings.php';
		require_once $include_path . 'class-notices.php';
		require_once $include_path . 'class-schema.php';
		require_once $include_path . 'class-config.php';
		require_once $include_path . 'class-feeds.php';
		require_once $include_path . 'class-utils.php';
		require_once $include_path . 'class-interfaces.php';
		require_once $include_path . 'logging.php';
	}

	/**
	 * Filter the WP single template path for a single node, either from the template hierarchy if available, or from the default location in the plugin (single_template filter)
	 *
	 * @param  string $template default template path from WP.
	 * @return string Template path
	 */
	public static function load_node_single_template( $template ) {

		if ( is_singular( 'murmurations_node' ) ) {
			$template = self::get_template_location( 'single-murmurations_node.php' );
		}

		return $template;
	}

	/**
	 * Filter the WP archive template path for the node archive page, either from the template hierarchy if available, or from the default location in the plugin (archive_template filter)
	 *
	 * @param  string $template default template path from WP.
	 * @return string Template path
	 */
	public static function load_node_archive_template( $template ) {
		if ( is_post_type_archive( 'murmurations_node' ) ) {
			$template = self::get_template_location( 'archive-murmurations_node.php' );
		}

		return $template;
	}

	/**
	 * Register the aggregator's hooks and enqueues and such
	 */
	public function register_hooks() {

		add_action( 'init', array( __CLASS__, 'register_cpts_and_taxes' ) );

		add_shortcode( Settings::get( 'plugin_slug' ) . '-directory', array( __CLASS__, 'show_directory' ) );
		add_shortcode( Settings::get( 'plugin_slug' ) . '-map', array( __CLASS__, 'show_map' ) );

		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		wp_enqueue_style( 'murmurations-agg-css', MURMAG_ROOT_URL . 'css/murmurations-aggregator.css', null, '1.0.0' );
		add_action( 'rest_api_init', array( __CLASS__, 'register_api_routes' ) );

		add_action( 'murmurations_node_update', array( __CLASS__, 'update_nodes' ) );

		add_filter( 'single_template', array( __CLASS__, 'load_node_single_template' ) );

		add_filter( 'archive_template', array( __CLASS__, 'load_node_archive_template' ) );

	}

	/**
	 * REST API endpoint for accessing locally stored nodes
	 *
	 * @param  array $req query string parameters that came with the request.
	 * @return string JSON of node data
	 */
	public static function rest_get_nodes( $req ) {

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
					// phpcs:ignore -- ignore the slowness of meta queries, since there's no good alternative
					$args['meta_query'] = $meta_query;
				}
			}
		}

		$nodes = self::get_nodes( $args );

		$rest_nodes = array();
		foreach ( $nodes as $node ) {
			if ( 'HTML' === $req['format'] ) {

				$rest_nodes[] = self::load_template( 'node_list_item.php', $node );

			} elseif ( 'KUMU' === $req['format'] ) {

				if ( ! isset( $node['label'] ) && isset( $node['name'] ) ) {
					$node['label'] = $node['name'];
				}

				$rest_nodes[] = $node;

			} else {
					$rest_nodes[] = $node;
			}
		}

		if ( 'KUMU' === $req['format'] ) {

			$rest_nodes = array(
				'elements' => $rest_nodes,
			);

		}

		return rest_ensure_response( $rest_nodes );
	}

	/**
	 * Register the API routes for accessing locally stored nodes
	 */
	public static function register_api_routes() {

		$result = register_rest_route(
			Settings::get( 'api_route' ),
			'get/nodes',
			array(
				'methods'  => 'GET',
				'callback' => array( __CLASS__, 'rest_get_nodes' ),
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
				'show_in_menu'  => true,
				'menu_position' => 21,
				'rewrite'       => array( 'slug' => Settings::get( 'node_slug' ) ),
				'supports'      => array(
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'custom-fields',
				),
			)
		);

	}
}
