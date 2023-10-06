<?php

if ( ! class_exists( 'Murmurations_Aggregator_API' ) ) {
	class Murmurations_Aggregator_API {
		private QM_DB|wpdb $wpdb;
		private string $table_name;
		private string $node_table_name;

		public function __construct() {
			global $wpdb;
			$this->wpdb            = $wpdb;
			$this->table_name      = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;
			$this->node_table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
		}

		public function register_api_routes(): void {
			$frontend_namespace = 'murmurations-aggregator/v1';
			$backend_namespace  = 'murmurations-aggregator/v1/api';

			// frontend
			register_rest_route(
				$frontend_namespace,
				'/maps/(?P<tag_slug>[\w]+)',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_map_nodes' ),
				),
			);

			// backend
			// Map Routes
			register_rest_route(
				$backend_namespace,
				'/maps',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'get_maps' ),
					),
					array(
						'methods'  => 'POST',
						'callback' => array( $this, 'post_map' ),
					),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/maps/(?P<map_id>[\d]+)',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'get_map' ),
					),
					array(
						'methods'  => 'PUT',
						'callback' => array( $this, 'put_map' ),
					),
					array(
						'methods'  => 'DELETE',
						'callback' => array( $this, 'delete_map' ),
					),
				)
			);

			register_rest_route(
				$backend_namespace,
				'/maps/(?P<map_id>[\d]+)/last-updated',
				array(
					'methods'  => 'PUT',
					'callback' => array( $this, 'put_map_last_updated' ),
				),
			);

			// WP Nodes Routes
			register_rest_route(
				$backend_namespace,
				'/wp-nodes/(?P<post_id>[\d]+)',
				array(
					array(
						'methods'  => 'GET',
						'callback' => array( $this, 'get_wp_node' )
					),
					array(
						'methods'  => 'PUT',
						'callback' => array( $this, 'put_wp_node' )
					),
					array(
						'methods'  => 'DELETE',
						'callback' => array( $this, 'delete_wp_node' ),
					),
				)
			);

			register_rest_route(
				$backend_namespace,
				'/wp-nodes',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_wp_node' ),
				),
			);

			register_rest_route(
				$backend_namespace,
				'wp-nodes/(?P<post_id>[\d]+)/restore',
				array(
					'methods'  => 'PUT',
					'callback' => array( $this, 'restore_wp_node' ),
				),
			);

			// Custom Nodes Routes
			register_rest_route(
				$backend_namespace,
				'/nodes',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_nodes' ),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/(?P<node_id>[\d]+)',
				array(
					array(
						'methods'  => 'PUT',
						'callback' => array( $this, 'put_node' ),
					),
					array(
						'methods'  => 'DELETE',
						'callback' => array( $this, 'delete_node' ),
					),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/(?P<node_id>[\d]+)/status',
				array(
					'methods'  => 'PUT',
					'callback' => array( $this, 'put_node_status' ),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_node' ),
				),
			);
		}

		public function get_map_nodes( $request ): WP_REST_Response|WP_Error {
			$tag_slug = $request->get_param( 'tag_slug' );
			$view = $request->get_param( 'view' );

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

				if ($view === 'dict') {
					$map[] = [
						'id' => get_the_ID(),
						'name' => get_the_title(),
						'lon' => get_post_meta( get_the_ID(), 'murmurations_geolocation_lon', true ),
						'lat' => get_post_meta( get_the_ID(), 'murmurations_geolocation_lat', true ),
					];
				} else {
					$map[] = [
						get_post_meta( get_the_ID(), 'murmurations_geolocation_lon', true ),
						get_post_meta( get_the_ID(), 'murmurations_geolocation_lat', true ),
						get_the_ID(),
					];
				}
			}

			wp_reset_postdata();

			return rest_ensure_response( $map );
		}

		public function get_maps(): WP_REST_Response|WP_Error {
			$query    = "SELECT * FROM $this->table_name";
			$map_data = $this->wpdb->get_results( $query );

			if ( ! $map_data ) {
				return new WP_Error( 'no_data_found', 'No map data found', array( 'status' => 404 ) );
			}

			return rest_ensure_response( $map_data );
		}

		public function post_map( $request ): WP_REST_Response|WP_Error {
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

		public function get_map( $request ): WP_REST_Response|WP_Error {
			$map_id = $request->get_param( 'map_id' );

			$query    = $this->wpdb->prepare( "SELECT * , UNIX_TIMESTAMP(last_updated) as last_updated FROM $this->table_name WHERE id = %s", $map_id );
			$map_data = $this->wpdb->get_row( $query );

			if ( ! $map_data ) {
				return new WP_Error( 'no_data_found', 'No map data found', array( 'status' => 404 ) );
			}

			return rest_ensure_response( $map_data );
		}

		public function put_map( $request ): WP_REST_Response|WP_Error {
			$map_id = $request->get_param( 'map_id' );

			$data = $request->get_json_params();

			$result = $this->wpdb->update( $this->table_name, array(
				'name'           => $data['name'],
				'map_center_lon' => ! empty( $data['map_center_lon'] ) ? sanitize_text_field( $data['map_center_lon'] ) : '1.8883340',
				'map_center_lat' => ! empty( $data['map_center_lat'] ) ? sanitize_text_field( $data['map_center_lat'] ) : '46.6033540',
				'map_scale'      => ! empty( $data['map_scale'] ) ? sanitize_text_field( $data['map_scale'] ) : '5',
			), array(
				'id' => $map_id,
			) );

			if ( ! $result ) {
				return new WP_Error( 'map_update_failed', 'Failed to update map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map updated successfully.' );
		}

		public function delete_map( $request ): WP_REST_Response|WP_Error {
			$map_id = $request->get_param( 'map_id' );

			// validate
			if ( ! isset( $map_id ) ) {
				return new WP_Error( 'invalid_map_id', 'Invalid map_id provided', array( 'status' => 400 ) );
			}

			// check map is exist or not
			$query = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $map_id );

			$map = $this->wpdb->get_row( $query );

			if ( ! $map ) {
				return new WP_Error( 'map_not_found', 'Map not found', array( 'status' => 404 ) );
			}

			// get all nodes and delete wordpress posts
			$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d", $map_id );

			$nodes = $this->wpdb->get_results( $query );

			if ( $nodes ) {
				foreach ( $nodes as $node ) {
					$post_id = $node->post_id;
					wp_delete_post( $post_id, true );
				}

				// delete tags
				$tag_slug = $map->tag_slug;
				$tag      = get_term_by( 'slug', $tag_slug, 'murmurations_node_tags' );
				wp_delete_term( $tag->term_id, 'murmurations_node_tags' );
			}

			// delete map
			$result = $this->wpdb->delete( $this->table_name, array( 'id' => $map_id ) );

			if ( ! $result ) {
				return new WP_Error( 'map_deletion_failed', 'Failed to delete map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map deleted successfully.' );
		}

		public function put_map_last_updated( $request ): WP_REST_Response|WP_Error {
			$map_id = $request->get_param( 'map_id' );
			$data   = $request->get_json_params();

			if ( ! isset( $map_id ) || ! isset( $data['last_updated'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			$result = $this->wpdb->update( $this->table_name, array(
				'last_updated' => date( "Y-m-d H:i:s", $data['last_updated'] / 1000 ),
			), array(
				'id' => $map_id,
			) );

			if ( ! $result ) {
				return new WP_Error( 'map_update_failed', 'Failed to update map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map updated successfully.' );
		}

		public function get_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );

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

		public function put_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );
			$data    = $request->get_json_params();

			$result = wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $data['profile_data']['name'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'post_update_failed', 'Failed to update post.', array( 'status' => 500 ) );
			}

			// update custom fields
			update_post_meta( $post_id, 'murmurations_description', $data['profile_data']['description'] );
			update_post_meta( $post_id, 'murmurations_geolocation_lon', $data['profile_data']['geolocation']['lon'] );
			update_post_meta( $post_id, 'murmurations_geolocation_lat', $data['profile_data']['geolocation']['lat'] );

			return rest_ensure_response( 'Node updated successfully.' );
		}

		public function delete_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );

			// delete post
			$result = wp_delete_post( $post_id, true );

			if ( ! $result ) {
				return new WP_Error( 'wp_post_deletion_failed', 'Failed to delete post.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node deleted successfully.' );
		}

		public function post_wp_node( $request ): WP_REST_Response|WP_Error {
			$data     = $request->get_json_params();
			$tag_slug = sanitize_text_field( $data['data']['tag_slug'] );

			// validate profile
			if ( ! isset( $data['profile_data'] ) || ! isset( $tag_slug ) || ! isset( $data['data']['map_id'] ) ) {
				return new WP_Error( 'invalid_data', 'profile field is required.', array( 'status' => 400 ) );
			}

			// create a post
			$post_id = wp_insert_post( array(
				'post_title'  => $data['profile_data']['name'],
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
				update_post_meta( $post_id, 'murmurations_description', $data['profile_data']['description'] );
				update_post_meta( $post_id, 'murmurations_geolocation_lon', $data['profile_data']['geolocation']['lon'] );
				update_post_meta( $post_id, 'murmurations_geolocation_lat', $data['profile_data']['geolocation']['lat'] );
			}

			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'post_creation_failed', 'Failed to create post.', array( 'status' => 500 ) );
			}

			// update status in node table
			$result = $this->wpdb->update( $this->node_table_name, array(
				'post_id' => $post_id,
				'status'  => 'publish',
			), array(
				'profile_url' => $data['index_data']['profile_url'],
				'map_id'      => $data['data']['map_id'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'WP Node created successfully.' );
		}

		public function restore_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );

			$trashed_post = get_post( $post_id );

			if ( ! $trashed_post || $trashed_post->post_status !== 'trash' ) {
				return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
			}

			$result = wp_untrash_post( $post_id );

			if ( ! $result ) {
				return new WP_Error( 'wp_post_restore_failed', 'Failed to restore post.', array( 'status' => 500 ) );
			}

			wp_publish_post($post_id);

			return rest_ensure_response( 'Node restored successfully.' );
		}

		public function get_nodes( $request ): WP_REST_Response|WP_Error {
			$mapId      = $request->get_param( 'map_id' );
			$profileUrl = $request->get_param( 'profile_url' );

			if ( ! isset( $mapId ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			if ( isset( $profileUrl ) ) {
				$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d AND profile_url = %s", $mapId, $profileUrl );
			} else {
				$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d", $mapId );
			}

			$nodes = $this->wpdb->get_results( $query );

			if ( ! $nodes ) {
				return new WP_Error( 'no_data_found', 'No node data found', array( 'status' => 404 ) );
			}

			// get map information and replace map_id field
			$map = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $mapId ) );

			if ( ! $map ) {
				return new WP_Error( 'map_not_found', 'Map not found', array( 'status' => 404 ) );
			}

			// json decode data
			foreach ( $nodes as $node ) {
				$node->profile_data = json_decode( $node->data );
				unset( $node->data );
				$node->map = $map;
				unset( $node->map_id );
			}

			return rest_ensure_response( $nodes );
		}

		public function put_node( $request ): WP_REST_Response|WP_Error {
			$node_id = $request->get_param( 'node_id' );
			$data    = $request->get_json_params();

			// validate data
			if ( ! isset( $node_id ) || ! isset( $data['data'] ) || ! isset( $data['profile_data'] ) || ! isset( $data['index_data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// update data
			$result = $this->wpdb->update( $this->node_table_name, array(
				'data'         => json_encode( $data['profile_data'] ),
				'last_updated' => $data['index_data']['last_updated'],
			), array(
				'profile_url' => $data['index_data']['profile_url'],
				'map_id'      => $data['data']['map_id'],
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_update_failed', 'Failed to update node.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node updated successfully.' );
		}

		public function delete_node( $request ): WP_REST_Response|WP_Error {
			$node_id = $request->get_param( 'node_id' );

			// validate data
			if ( ! isset( $node_id ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// delete node
			$result = $this->wpdb->delete( $this->node_table_name, array(
				'id' => $node_id,
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_deletion_failed', 'Failed to delete node.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node deleted successfully.' );
		}

		public function put_node_status( $request ): WP_REST_Response|WP_Error {
			$node_id = $request->get_param( 'node_id' );
			$data    = $request->get_json_params();

			// validate data
			if ( ! isset( $node_id ) || ! isset( $data['data'] ) || ! isset( $data['profile_data'] ) || ! isset( $data['index_data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// update status in node table
			// if the status is 'dismiss' or 'ignore', set 'post_id' to null
			if ( $data['data']['status'] === 'dismiss' || $data['data']['status'] === 'ignore' ) {
				$result = $this->wpdb->update( $this->node_table_name, array(
					'status'  => $data['data']['status'],
					'post_id' => null,
				), array(
					'profile_url' => $data['index_data']['profile_url'],
					'map_id'      => $data['data']['map_id'],
				) );
			} else {
				$result = $this->wpdb->update( $this->node_table_name, array(
					'status' => $data['data']['status'],
				), array(
					'profile_url' => $data['index_data']['profile_url'],
					'map_id'      => $data['data']['map_id'],
				) );
			}

			if ( ! $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node status updated successfully.' );
		}

		public function post_node( $request ): WP_REST_Response|WP_Error {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['profile_data'] ) || ! isset( $data['data'] ) || ! isset( $data['index_data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			// insert data
			$result = $this->wpdb->insert( $this->node_table_name, array(
				'profile_url'  => $data['index_data']['profile_url'],
				'map_id'       => $data['data']['map_id'],
				'data'         => json_encode( $data['profile_data'] ),
				'last_updated' => $data['index_data']['last_updated'],
				'status'       => $data['data']['status'] ?? 'new',
			) );

			if ( ! $result ) {
				return new WP_Error( 'node_creation_failed', 'Failed to create node.', array( 'status' => 500 ) );
			}

			$inserted_id = $this->wpdb->insert_id;

			$response = array(
				'node_id' => $inserted_id,
			);

			return rest_ensure_response( $response );
		}
	}
}