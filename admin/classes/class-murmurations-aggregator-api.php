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
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'get_map' ),
					),
					array(
						'methods'  => 'PUT',
						'callback' => array( $this, 'put_map' ),
					),
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
				'/maps/(?P<map_id>[\w]+)',
				array(
					'methods'  => 'DELETE',
					'callback' => array( $this, 'delete_map' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/wp-nodes/(?P<post_id>[\w]+)',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_wp_node' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/wp-nodes',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_wp_node' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/nodes-comparison',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_nodes_comparison' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/nodes-status',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_node_status' ),
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

			$args = array(
				'post_type'      => 'murmurations_node',
				'posts_per_page' => - 1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'murmurations_node_tags',
						'field'    => 'slug',
						'terms'    => $tag_slug,
					),
				),
			);

			$query = new WP_Query( $args );

			if ( ! $query->have_posts() ) {
				return new WP_Error( 'no_posts_found', 'No posts found', array( 'status' => 404 ) );
			}

			$map = [];

			while ( $query->have_posts() ) {
				$query->the_post();
				$map[] = [
					get_post_meta( get_the_ID(), 'murmurations_geolocation_lon', true ),
					get_post_meta( get_the_ID(), 'murmurations_geolocation_lat', true ),
					get_the_ID(),
				];
			}

			wp_reset_postdata();

			return rest_ensure_response( $map );
		}

		public function put_map( $request ) {
			$tag_slug = $request->get_param( 'tag_slug' );

			$data = $request->get_json_params();

			$result = $this->wpdb->update( $this->table_name, array(
				'name'           => $data['name'],
				'map_center_lon' => ! empty( $data['map_center_lon'] ) ? sanitize_text_field( $data['map_center_lon'] ) : '1.8883340',
				'map_center_lat' => ! empty( $data['map_center_lat'] ) ? sanitize_text_field( $data['map_center_lat'] ) : '46.6033540',
				'map_scale'      => ! empty( $data['map_scale'] ) ? sanitize_text_field( $data['map_scale'] ) : '5',
			), array(
				'tag_slug' => $tag_slug,
			) );

			if ( ! $result ) {
				return new WP_Error( 'map_update_failed', 'Failed to update map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map updated successfully.' );
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

			// If we can find same index_url and query_url in db, return 400
			$query = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE index_url = %s AND query_url = %s", $data['index_url'], $data['query_url'] );

			$map_data = $this->wpdb->get_results( $query );

			if ( $map_data ) {
				return new WP_Error( 'map_already_exists', 'Map already exists for the provided index_url and query_url', array( 'status' => 400 ) );
			}

			// insert data
			$result = $this->wpdb->insert( $this->table_name, array(
				'name'           => $data['name'],
				'index_url'      => $data['index_url'],
				'query_url'      => $data['query_url'],
				'tag_slug'       => $data['tag_slug'],
				'map_center_lon' => ! empty( $data['map_center_lon'] ) ? sanitize_text_field( $data['map_center_lon'] ) : '1.8883340',
				'map_center_lat' => ! empty( $data['map_center_lat'] ) ? sanitize_text_field( $data['map_center_lat'] ) : '46.6033540',
				'map_scale'      => ! empty( $data['map_scale'] ) ? sanitize_text_field( $data['map_scale'] ) : '5',
			) );

			if ( ! $result ) {
				return new WP_Error( 'map_creation_failed', 'Failed to create map.', array( 'status' => 500 ) );
			}

			$inserted_id = $this->wpdb->insert_id;

			return rest_ensure_response( array( 'map_id' => $inserted_id ) );
		}

		public function delete_map( $request ) {
			// validate map_id
			$map_id = $request->get_param( 'map_id' );
			if ( ! $map_id ) {
				return new WP_Error( 'invalid_map_id', 'Invalid map_id provided', array( 'status' => 400 ) );
			}

			// get all nodes and delete wordpress posts
			$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d", $map_id );

			$nodes = $this->wpdb->get_results( $query );

			foreach ( $nodes as $node ) {
				$post_id = $node->post_id;
				wp_delete_post( $post_id, true );
			}

			// delete tags
			$tag_slug = $nodes[0]->tag_slug;
			$tag      = get_term_by( 'slug', $tag_slug, 'murmurations_node_tags' );
			wp_delete_term( $tag->term_id, 'murmurations_node_tags' );

			// delete map
			$result = $this->wpdb->delete( $this->table_name, array( 'id' => $map_id ) );

			if ( ! $result ) {
				return new WP_Error( 'map_deletion_failed', 'Failed to delete map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map deleted successfully.' );
		}

		public function get_wp_node( $request ) {
			$post_id = $request['post_id'];

			$post = get_post( $post_id );

			if ( ! $post || $post->post_type !== 'murmurations_node' ) {
				return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
			}

			$response = array(
				'title'       => $post->post_title,
				'post_url'    => get_permalink( $post_id ),
				'description' => get_post_meta( $post_id, 'murmurations_description', true ),
			);

			return rest_ensure_response( $response );
		}

		public function post_wp_node( $request ) {
			$data     = $request->get_json_params();
			$tag_slug = sanitize_text_field( $data['tag_slug'] );

			// validate profile
			if ( ! isset( $data['profile'] ) ) {
				return new WP_Error( 'invalid_data', 'profile field is required.', array( 'status' => 400 ) );
			}

			// create a post
			$post_id = wp_insert_post( array(
				'post_title'  => $data['profile']['name'],
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

			// set custom fields
			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'murmurations_description', $data['profile']['profile_data']['description'] );
				update_post_meta( $post_id, 'murmurations_geolocation_lon', $data['profile']['profile_data']['geolocation']['lon'] );
				update_post_meta( $post_id, 'murmurations_geolocation_lat', $data['profile']['profile_data']['geolocation']['lat'] );
			}


			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'post_creation_failed', 'Failed to create post.', array( 'status' => 500 ) );
			}

			// update status in node table
			$result = $this->wpdb->update( $this->node_table_name, array(
				'post_id' => $post_id,
				'status'  => 'publish',
			), array(
				'profile_url' => $data['profile']['profile_url'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return array(
				'message' => 'Node created successfully.',
				'post_id' => $post_id,
			);
		}

		public function post_nodes_comparison( $request ) {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['map_id'] ) || ! isset( $data['data'] ) || ! isset( $data['profile_url'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// hash the data
			$encodedJson = json_encode( $data['data'] );
			$hashed_data = hash( $this->hash_algorithm, $encodedJson );

			// find data in nodes table by profile_url
			$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE profile_url = %s", $data['profile_url'] );

			$node = $this->wpdb->get_row( $query );

			if ( ! $node ) {
				return new WP_Error( 'node_not_found', 'Node not found', array( 'status' => 404 ) );
			}

			// handle mismatch and ignore
			if ( $node->hashed_data !== $hashed_data ) {
				return rest_ensure_response( array(
					'status' => $node->status,
					'has_update' => true,
				) );
			}

			// return node status
			return rest_ensure_response( array(
				'status' => $node->status,
				'has_update' => false,
			) );
		}

		public function post_node_status( $request ) {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['profile_url'] ) || ! isset( $data['status'] ) || ! isset( $data['map_id'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// update status in node table
			$result = $this->wpdb->update( $this->node_table_name, array(
				'status' => $data['status'],
			), array(
				'profile_url' => $data['profile_url'],
				'map_id'      => $data['map_id'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node status updated successfully.' );
		}

		public function post_node( $request ) {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['profile_url'] ) || ! isset( $data['map_id'] ) || ! isset( $data['data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// hash the data
			$encodedJson = json_encode( $data['data'] );
			$hashed_data = hash( $this->hash_algorithm, $encodedJson );

			// insert data
			$result = $this->wpdb->insert( $this->node_table_name, array(
				'profile_url' => $data['profile_url'],
				'map_id'      => $data['map_id'],
				'data'        => $encodedJson,
				'hashed_data' => $hashed_data,
				'status'      => $data['status'] ?? 'new',
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_creation_failed', 'Failed to create node.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node created successfully.' );
		}
	}
}