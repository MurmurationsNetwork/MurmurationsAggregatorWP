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

    if( is_admin() ){
      Admin::init();
    }

	}

	/**
	 * Activate the plugin
	 */
	public static function activate() {

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
   * @param  string $template the filename of the template
   * @return string|boolean full path of template file or false if template wasn't found anywhere
   */
  public static function get_template_location( $template ){

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

    if( $location ) {
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
        $nodes[ $post->ID ] = new Node( $post );
      }
    } else {
			llog( 'No node posts found in get_nodes using args: ' . print_r( $args, true ) );
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
   *
   *
   */
  public static function ajax_get_local_schema() {

    $schema = Schema::get();

    if (! $schema ) {
      $status = 'failed';
    } else {
      $status = 'success';
    }

    wp_send_json( array(
      'status'   => $status,
      'schema' => $schema,
      'messages' => Notices::get()
    ) );

  }

    /**
     * Set the last node update time after client-side node updates
     *
     *
     */
    public static function ajax_set_update_time() {

      Settings::set( 'update_time', time() );
      $result = Settings::save();

      if (! $result ) {
        $status = 'failed';
      } else {
        $status = 'success';
      }

      wp_send_json( array(
        'status'   => $status,
        'messages' => Notices::get()
      ) );

    }


  /**
   * Get index nodes by AJAX
   *
   *
   */
  public static function ajax_get_index_nodes() {
    $nodes = self::get_index_nodes();

    $result = array(
      'status'   => 'success',
      'messages' => Notices::get(),
      'nodes' => $nodes
    );

    if( ! $nodes ){
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

		$filters = $settings['filters'];

		llog( $filters, 'Filters from settings in get_index_nodes' );

		$all_index_nodes = array();

		foreach ( $settings['indices'] as $index_key => $index ) {

      if ( ( ! $index['disabled'] ) || ( $index['disabled'] === 'false' ) ){

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
          $index_filters[] = array( 'last_validated', 'isGreaterThan', $update_since );
  			}

  			$query = array();
  			foreach ( $index_filters as $filter ) {
  				$query[ $filter[0] ] = $filter[2];
  			}

        if( isset( $index['parameters'] ) ){
          foreach ($index['parameters'] as $pair) {
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

  			$index_nodes = API::getIndexJson( $index['url'], $query, $index_options );

  			$index_nodes = json_decode( $index_nodes, true );

        if ( ! $index_nodes ) {
          Notices::set( 'Could not parse index JSON from: ' . $index['url'], 'error' );
          llog( $index_nodes , "Could not parse index JSON" );
        } else {

    			$index_nodes = $index_nodes['data'];

          foreach ($index_nodes as $key => $node) {
            $index_nodes[$key]['index_options'] = $index;
          }

  				Notices::set( 'Fetched node info from index at ' . $index['url'], 'success' );
  				$all_index_nodes = array_merge( $all_index_nodes, $index_nodes );
  			}
      }
		}

		return $all_index_nodes;

  }

  public static function ajax_update_node(){

    $profile_url = $_POST['profile_url'];
    $index_options = $_POST['index_options'];

    $result = self::update_node( array(
      'profile_url' => $profile_url,
      'index_options' => $index_options
    ) );

    $feedback = array(
      'status'   => 'success',
      'messages' => Notices::get(),
    );

    if( ! $result ){
      $feedback['status'] = 'failed';
    }

    wp_send_json( $feedback );

  }


  public static function update_node( $data ){

    $url = $data['profile_url'];

    $options = array();

    if ( isset( $data['index_options']['use_api_key_for_nodes'] ) && isset( $data['index_options']['api_key'] ) ) {
      if ( $data['index_options']['use_api_key_for_nodes'] == 'true' ) {
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

    $node_json = API::getNodeJson( $url, $options );

    if ( ! $node_json ) {
      error( "Could not fetch node from $url" );
      return false;
    } else {

      $node_array = json_decode( $node_json, true );

      if ( ! $node_array ) {

        error( 'Attempted to build node from invalid JSON. Could not parse.');
        llog( $node_json, "Failed to parse node JSON from $url" );

        return false;

      } else {

        // Make sure the profile URL is included in the node data
        if( !isset( $node_array['profile_url'] ) ){
          $node_array['profile_url'] = $data['profile_url'];
        }

        Notices::set("Fetched JSON");

        $node = new Node( $node_array );

        if ( $node->hasErrors() ) {
          Notices::set( $node->getErrorsText(), 'error' );
          return false;
        }

        $filters = Settings::get('filters');

        if( is_array($filters) ){
          $matched = $node->checkFilters( $filters );
        } else {
          $matched = true;
        }


        if ( $matched == true ) {

          $result = $node->save();

          if ( ! $result ){
            Notices::set( 'Failed to save node: ' . $url, 'error' );
          } else {
            Notices::set( 'Node successfully saved', 'success' );
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

      if( !$result ){
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

		Settings::set( 'update_time', time() );
		Settings::save();

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
	public static function show_map() {
		$nodes = self::get_nodes();

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

		$html = self::leaflet_scripts();

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

		foreach ( $nodes as $key => $node ) {

			if ( isset( $node->data['geolocation']['lat'] ) && isset( $node->data['geolocation']['lon'] ) ) {

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
	public static function load_includes() {
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
	 * Filter the WP single template path for a single node, either from the template hierarchy if available, or from the default location in the plugin (single_template filter)
	 *
	 * @param  string $template default template path from WP
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
	 * @param  string $template default template path from WP
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

		wp_enqueue_style( 'murmurations-agg-css', MURMAG_ROOT_URL . 'css/murmurations-aggregator.css' );
		add_action( 'rest_api_init', array( __CLASS__, 'register_api_routes' ) );

		add_action( 'murmurations_node_update', array( __CLASS__, 'update_nodes' ) );

		add_filter( 'single_template', array( __CLASS__, 'load_node_single_template' ) );

		add_filter( 'archive_template', array( __CLASS__, 'load_node_archive_template' ) );

	}

	/**
	 * REST API endpoint for accessing locally stored nodes
	 *
	 * @param  array $req query string parameters that came with the request
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
					$args['meta_query'] = $meta_query;
				}
			}
		}

		$nodes = self::get_nodes( $args );

		$rest_nodes = array();
		foreach ( $nodes as $node ) {
      if ( 'HTML' === $req[ 'format' ] ){

  			$rest_nodes[] = self::load_template( 'node_list_item.php', $node->data );

      }else if ( 'KUMU' === $req[ 'format' ] ){

        if( !isset( $node->data['label'] ) && isset( $node->data['name'] ) ){
          $node->data['label'] = $node->data['name'];
        }

  			$rest_nodes[] = $node->data;

      }else{
			  $rest_nodes[] = $node->data;
      }
		}

    if ( 'KUMU' === $req[ 'format' ] ){

      $rest_nodes = array(
        "elements" => $rest_nodes
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
				'show_in_menu'  => true, // 'admin.php?page=murmurations-aggregator-settings',
				'menu_position' => 21,
				'rewrite'       => array( 'slug' => Settings::get( 'node_slug' ) ),
        'supports' => array(
          'title',
          'editor',
          'excerpt',
          'thumbnail',
          'custom-fields'
        )
			)
		);

	}
}
