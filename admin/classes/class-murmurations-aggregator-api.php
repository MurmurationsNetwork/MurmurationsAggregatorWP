<?php

if ( ! class_exists( 'Murmurations_Aggregator_API' ) ) {
	class Murmurations_Aggregator_API {
		private $wpdb;
		private $table_name;
		private $node_table_name;
		private $hash_algorithm;

		public function __construct() {
			global $wpdb;
			$this->wpdb            = $wpdb;
			$this->table_name      = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;
			$this->node_table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;
			$this->hash_algorithm  = 'sha256';

			add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
		}

		public function register_api_routes() {
			register_rest_route(
				'murmurations-aggregator/v1',
				'/maps/(?P<tag_slug>[\w]+)',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_map' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/maps',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'get_maps' ),
					),
					array(
						'methods'  => 'POST',
						'callback' => array( $this, 'post_map' )
					),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/wp_nodes',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_wp_node' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/nodes',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_node' ),
				)
			);
		}

		public function get_map( $request ) {
			$tag_slug = $request->get_param( 'tag_slug' );

			$query    = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE tag_slug = %s", $tag_slug );
			$map_data = $this->wpdb->get_results( $query );

			if ( ! $map_data ) {
				return new WP_Error( 'no_data_found', 'No map data found for the provided tag_slug', array( 'status' => 404 ) );
			}

			return rest_ensure_response( $map_data );
		}

		public function get_maps() {
			$query    = "SELECT * FROM $this->table_name";
			$map_data = $this->wpdb->get_results( $query );

			if ( ! $map_data ) {
				return new WP_Error( 'no_data_found', 'No map data found', array( 'status' => 404 ) );
			}

			return rest_ensure_response( $map_data );
		}

		public function post_map( $request ) {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['name'] ) || ! isset( $data['index_url'] ) || ! isset( $data['query_url'] ) || ! isset( $data['tag_slug'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// check if map already exists
			$query    = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE tag_slug = %s", $data['tag_slug'] );
			$map_data = $this->wpdb->get_results( $query );

			if ( $map_data ) {
				return new WP_Error( 'map_already_exists', 'Map already exists for the provided tag_slug', array( 'status' => 400 ) );
			}

			// insert data
			$result = $this->wpdb->insert( $this->table_name, array(
				'name'      => $data['name'],
				'index_url' => $data['index_url'],
				'query_url' => $data['query_url'],
				'tag_slug'  => $data['tag_slug'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'map_creation_failed', 'Failed to create map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map created successfully.' );
		}

		public function post_wp_node( $request ) {
			$data = $request->get_json_params();

			$post_title = sanitize_text_field( $data['name'] );
			$tag_slug   = sanitize_text_field( $data['tag_slug'] );

			// create a post
			$post_id = wp_insert_post( array(
				'post_title'  => $post_title,
				'post_type'   => 'murmurations_node',
				'post_status' => 'publish',
			) );

			// set tags
			if ( ! is_wp_error( $post_id ) && taxonomy_exists( 'murmurations_node_tags' ) ) {
				$tag = get_term_by( 'slug', $tag_slug, 'murmurations_node_tags' );
				if ( ! $tag ) {
					wp_insert_term( $tag_slug, 'murmurations_node_tags' );
					$tag = get_term_by( 'slug', $tag_slug, 'murmurations_node_tags' );
				}

				wp_set_post_terms( $post_id, array( $tag->term_id ), 'murmurations_node_tags' );
			}

			// todo: set custom fields

			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'post_creation_failed', 'Failed to create post.', array( 'status' => 500 ) );
			}

			return array(
				'message' => 'Node created successfully.',
				'post_id' => $post_id,
			);
		}

		public function post_node( $request ) {
			$data = $request->get_json_params();

//			return $this->wpdb->last_error;

			// validate data
			if ( ! isset( $data['profile_url'] ) || ! isset( $data['data'] ) || ! isset( $data['tag_slug'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// check if node already exists
			$query     = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE profile_url = %s", $data['profile_url'] );
			$node_data = $this->wpdb->get_results( $query );

			if ( $node_data ) {
				return new WP_Error( 'node_already_exists', 'Node already exists for the provided profile_url', array( 'status' => 400 ) );
			}

			// hash the data
			$encodedJson = json_encode( $data['data'] );
			$hashed_data = hash( $this->hash_algorithm, $encodedJson );

			// insert data
			$result = $this->wpdb->insert( $this->node_table_name, array(
				'profile_url' => $data['profile_url'],
				'tag_slug'    => $data['tag_slug'],
				'data'        => $encodedJson,
				'hashed_data' => $hashed_data,
				'status'      => $data['status'] ?? 'ignored',
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_creation_failed', 'Failed to create node.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node created successfully.' );
		}
	}
}