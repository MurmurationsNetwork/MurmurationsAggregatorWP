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
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_map_nodes' ),
					'permission_callback' => '__return_true',
				),
			);

			// backend
			// Map Routes
			register_rest_route(
				$backend_namespace,
				'/maps',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_maps' ),
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'post_map' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/maps/(?P<map_id>[\d]+)',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_map' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'put_map' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_map' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
				)
			);

			register_rest_route(
				$backend_namespace,
				'/maps/(?P<map_id>[\d]+)/last-updated',
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'put_map_last_updated' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/maps-dropdown',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_maps_dropdown' ),
					'permission_callback' => '__return_true',
				),
			);

			// WP Nodes Routes
			register_rest_route(
				$backend_namespace,
				'/wp-nodes/(?P<post_id>[\d]+)',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_wp_node' ),
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'put_wp_node' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_wp_node' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
				)
			);

			register_rest_route(
				$backend_namespace,
				'/wp-nodes',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_wp_node' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/wp-nodes/(?P<post_id>[\d]+)/restore',
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'restore_wp_node' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			// Custom Nodes Routes
			register_rest_route(
				$backend_namespace,
				'/nodes',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_nodes' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/primary-url-map',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_nodes_primary_url_map' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/(?P<node_id>[\d]+)',
				array(
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'put_node' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_node' ),
						'permission_callback' => function () {
							return current_user_can( 'activate_plugins' );
						},
					),
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/(?P<node_id>[\d]+)/status',
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'put_node_status' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes/(?P<node_id>[\d]+)/authority',
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'put_node_authority' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			register_rest_route(
				$backend_namespace,
				'/nodes',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_node' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);

			// API proxy
			register_rest_route(
				$backend_namespace,
				'/proxy',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_proxy' ),
					'permission_callback' => function () {
						return current_user_can( 'activate_plugins' );
					},
				),
			);
		}

		public function get_map_nodes( $request ): WP_REST_Response|WP_Error {
			$tag_slug = $request->get_param( 'tag_slug' );
			$view     = $request->get_param( 'view' );
			$params   = $request->get_params();

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

			$map = array();

			while ( $query->have_posts() ) {
				$query->the_post();
				$profile_data  = get_post_meta( get_the_ID(), 'murmurations_profile_data', true );
				$profile_query = $this->wpdb->prepare( "SELECT profile_url FROM $this->node_table_name WHERE post_id = %d", get_the_ID() );
				$node          = $this->wpdb->get_row( $profile_query );

				$latitude  = $profile_data['geolocation']['lat'] ?? '';
				$longitude = $profile_data['geolocation']['lon'] ?? '';

				// Check if schema starts with complimentary_currencies
				if ( isset( $profile_data['linked_schemas'] ) && is_array( $profile_data['linked_schemas'] ) ) {
					foreach ( $profile_data['linked_schemas'] as $schema ) {
						if ( str_contains( $schema, 'complementary_currencies' ) ) {
							$latitude  = $profile_data['latitude'] ?? $latitude;
							$longitude = $profile_data['longitude'] ?? $longitude;
							break;
						}
					}
				}

				if ( $this->matches_search_criteria( $profile_data, $params ) ) {
					if ( 'dir' === $view ) {
						$map[] = array(
							'id'           => get_the_ID(),
							'name'         => get_the_title(),
							'post_url'     => get_permalink(),
							'profile_data' => $profile_data,
							'profile_url'  => $node->profile_url ?? '',
						);
					} else {
						$map[] = array(
							$longitude,
							$latitude,
							get_the_ID(),
							$node->profile_url ?? '',
						);
					}
				}
			}

			wp_reset_postdata();

			return rest_ensure_response( $map );
		}

		private function matches_search_criteria( $profile_data, $params ): bool {
			foreach ( $params as $key => $value ) {
				if ( ! in_array( $key, array( 'view', 'tag_slug' ), true ) ) {
					// If the profile data key is not set, return false
					if ( ! isset( $profile_data[ $key ] ) ) {
						return false;
					}

					// Array values need to be compared differently
					// This is for tags, we're using `or` logic, any match should return true
					if ( is_array( $profile_data[ $key ] ) ) {
						$input_tags = array_map( 'trim', explode( ',', $value ) );

						$match_found = false;
						foreach ( $input_tags as $tag ) {
							if ( in_array( strtolower( $tag ), array_map( 'strtolower', $profile_data[ $key ] ), true ) ) {
								$match_found = true;
								break;
							}
						}

						if ( ! $match_found ) {
							return false;
						}
					} elseif ( strtolower( $profile_data[ $key ] ) !== strtolower( $value ) ) {
						// If the profile data value is not an array, compare directly
						return false;
					}
				}
			}

			return true;
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
			$result = $this->wpdb->insert(
				$this->table_name,
				array(
					'name'           => $data['name'],
					'index_url'      => $data['index_url'],
					'query_url'      => $data['query_url'],
					'tag_slug'       => $data['tag_slug'],
					'map_center_lat' => ! empty( $data['map_center_lat'] ) ? sanitize_text_field( $data['map_center_lat'] ) : '48.86',
					'map_center_lon' => ! empty( $data['map_center_lon'] ) ? sanitize_text_field( $data['map_center_lon'] ) : '2.34',
					'map_scale'      => ! empty( $data['map_scale'] ) ? sanitize_text_field( $data['map_scale'] ) : '5',
				)
			);

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

			$result = $this->wpdb->update(
				$this->table_name,
				array(
					'name'           => $data['name'],
					'map_center_lon' => ! empty( $data['map_center_lon'] ) ? sanitize_text_field( $data['map_center_lon'] ) : '1.8883340',
					'map_center_lat' => ! empty( $data['map_center_lat'] ) ? sanitize_text_field( $data['map_center_lat'] ) : '46.6033540',
					'map_scale'      => ! empty( $data['map_scale'] ) ? sanitize_text_field( $data['map_scale'] ) : '5',
				),
				array(
					'id' => $map_id,
				)
			);

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

			// get all nodes and delete WordPress posts
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
				if ( $tag && isset( $tag->term_id ) ) {
					wp_delete_term( $tag->term_id, 'murmurations_node_tags' );
				}
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

			$result = $this->wpdb->update(
				$this->table_name,
				array(
					'last_updated' => gmdate( 'Y-m-d H:i:s', $data['last_updated'] / 1000 ),
				),
				array(
					'id' => $map_id,
				)
			);

			if ( ! $result ) {
				return new WP_Error( 'map_update_failed', 'Failed to update map.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Map updated successfully.' );
		}

		public function get_maps_dropdown( $request ): WP_REST_Response|WP_Error {
			$tag_slug = $request->get_param( 'tag_slug' );
			if ( empty( $tag_slug ) ) {
				return new WP_Error( 'missing_param', 'The tag_slug parameter is required', array( 'status' => 400 ) );
			}

			$query  = $this->wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE tag_slug = %s LIMIT 1",
				$tag_slug
			);
			$result = $this->wpdb->get_row( $query );

			if ( null === $result ) {
				return new WP_Error( 'no_data_found', 'No data found for the given tag_slug', array( 'status' => 404 ) );
			}

			$query_url = $result->query_url;
			// Parse the query URL
			parse_str( wp_parse_url( $query_url, PHP_URL_QUERY ), $query_params );
			$schema = $query_params['schema'] ?? '';

			if ( empty( $schema ) ) {
				return new WP_Error( 'invalid_query_url', 'The query_url does not contain a valid schema parameter', array( 'status' => 400 ) );
			}

			// Get schema data from the schema URL
			$schema_url = 'https://test-library.murmurations.network/v2/schemas/' . $schema;
			$response   = wp_remote_get( $schema_url );

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'external_request_failed', 'Failed to fetch schema data', array( 'status' => 500 ) );
			}

			$body        = wp_remote_retrieve_body( $response );
			$schema_data = json_decode( $body, true );

			if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $schema_data['properties'] ) ) {
				return new WP_Error( 'invalid_schema_response', 'Invalid schema data received', array( 'status' => 500 ) );
			}

			// Get All enum values from the schema
			$dropdown_items = array();
			foreach ( $schema_data['properties'] as $property_name => $property_data ) {
				if ( isset( $property_data['enum'] ) ) {
					$enum_names = ! empty( $property_data['enumNames'] )
						? $property_data['enumNames']
						: $property_data['enum'];

					$dropdown_items[] = array(
						'field_name' => $property_name,
						'title'      => $property_data['title'] ?? $property_name,
						'options'    => array_map(
							function ( $enum_value, $enum_name ) {
								return array(
									'label' => $enum_name,
									'value' => $enum_value,
								);
							},
							$property_data['enum'],
							$enum_names
						),
					);
				}
			}

			return new WP_REST_Response( $dropdown_items, 200 );
		}

		public function get_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );

			$post = get_post( $post_id );

			if ( ! $post || 'murmurations_node' !== $post->post_type ) {
				return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
			}

			$response = array(
				'title'        => $post->post_title,
				'post_url'     => get_permalink( $post_id ),
				'profile_data' => get_post_meta( $post_id, 'murmurations_profile_data', true ),
			);

			return rest_ensure_response( $response );
		}

		public function put_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id    = $request->get_param( 'post_id' );
			$data       = $request->get_json_params();
			$post_title = $data['profile_data']['name'] ?? $data['profile_data']['title'];

			$result = wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $post_title,
				)
			);

			if ( ! $result ) {
				return new WP_Error( 'post_update_failed', 'Failed to update post.', array( 'status' => 500 ) );
			}

			// update custom fields
			update_post_meta( $post_id, 'murmurations_profile_data', $data['profile_data'] );

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
			$post_title = $data['profile_data']['name'] ?? $data['profile_data']['title'];

			$post_id = wp_insert_post(
				array(
					'post_title'   => $post_title,
					'post_type'    => 'murmurations_node',
					'post_status'  => 'publish',
					'post_excerpt' => '[murmurations_excerpt]',
				)
			);

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
				update_post_meta( $post_id, 'murmurations_profile_data', $data['profile_data'] );

				// modify the template
				$custom_template = Murmurations_Aggregator_Utils::get_custom_template( $data['profile_data']['linked_schemas'][0] );
				if ( ! is_null( $custom_template ) ) {
					update_post_meta( $post_id, '_wp_page_template', $custom_template );
				}
			}

			if ( is_wp_error( $post_id ) ) {
				return new WP_Error( 'post_creation_failed', 'Failed to create post.', array( 'status' => 500 ) );
			}

			// update status in node table
			$result = $this->wpdb->update(
				$this->node_table_name,
				array(
					'post_id' => $post_id,
					'status'  => 'publish',
				),
				array(
					'profile_url' => $data['index_data']['profile_url'],
					'map_id'      => $data['data']['map_id'],
				)
			);

			if ( ! $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'WP Node created successfully.' );
		}

		public function restore_wp_node( $request ): WP_REST_Response|WP_Error {
			$post_id = $request->get_param( 'post_id' );

			$trashed_post = get_post( $post_id );

			if ( ! $trashed_post || 'trash' !== $trashed_post->post_status ) {
				return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
			}

			$result = wp_untrash_post( $post_id );

			if ( ! $result ) {
				return new WP_Error( 'wp_post_restore_failed', 'Failed to restore post.', array( 'status' => 500 ) );
			}

			wp_publish_post( $post_id );

			return rest_ensure_response( 'Node restored successfully.' );
		}

		public function get_nodes( $request ): WP_REST_Response|WP_Error {
			$map_id       = $request->get_param( 'map_id' );
			$profile_url  = $request->get_param( 'profile_url' );
			$is_available = $request->get_param( 'is_available' );

			if ( ! isset( $map_id ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			if ( isset( $profile_url ) ) {
				$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d AND profile_url = %s", $map_id, $profile_url );
			} else {
				$query = $this->wpdb->prepare( "SELECT * FROM $this->node_table_name WHERE map_id = %d", $map_id );
			}

			if ( isset( $is_available ) ) {
				if ( 'false' === $is_available ) {
					$is_available = 0;
				} else {
					$is_available = 1;
				}
				$query .= $this->wpdb->prepare( ' AND is_available = %d', $is_available );
			}

			$nodes = $this->wpdb->get_results( $query );

			if ( ! $nodes ) {
				return new WP_Error( 'no_data_found', 'No node data found', array( 'status' => 404 ) );
			}

			// get map information and replace map_id field
			$map = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE id = %d", $map_id ) );

			if ( ! $map ) {
				return new WP_Error( 'map_not_found', 'Map not found', array( 'status' => 404 ) );
			}

			// json decode data
			foreach ( $nodes as $node ) {
				$node->profile_data = json_decode( $node->data );
				unset( $node->data );
				$node->map = $map;
				unset( $node->map_id );

				// handle is_available field
				if ( '1' === $node->is_available ) {
					$node->is_available = true;
				} else {
					$node->is_available = false;
				}

				// handle unavailable_message field
				if ( null === $node->unavailable_message ) {
					$node->unavailable_message = '';
				}

				// handle has_authority field
				if ( '1' === $node->has_authority ) {
					$node->has_authority = true;
				} else {
					$node->has_authority = false;
				}
			}

			return rest_ensure_response( $nodes );
		}

		public function get_nodes_primary_url_map( $request ): WP_REST_Response|WP_Error {
			$map_id = $request->get_param( 'map_id' );
			if ( ! isset( $map_id ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			$query = $this->wpdb->prepare( "SELECT profile_url, data FROM $this->node_table_name WHERE map_id = %d", $map_id );

			$rows = $this->wpdb->get_results( $query );

			$primary_url_map = array();

			foreach ( $rows as $row ) {
				$profile_url = $row->profile_url;
				$data        = json_decode( $row->data, true );
				$primary_url = $data['primary_url'] ?? '';

				// Parse URLs to get hostnames
				$profile_host = wp_parse_url( $profile_url, PHP_URL_HOST );
				$primary_host = wp_parse_url( $primary_url, PHP_URL_HOST );

				if ( false !== $profile_host && false !== $primary_host && $profile_host === $primary_host ) {
					$primary_url_map[ $primary_host ] = 1;
				}
			}

			return rest_ensure_response( $primary_url_map );
		}

		public function put_node( $request ): WP_REST_Response|WP_Error {
			$node_id = $request->get_param( 'node_id' );
			$data    = $request->get_json_params();

			// validate data
			if ( ! isset( $node_id ) || ! isset( $data['data'] ) || ! isset( $data['profile_data'] ) || ! isset( $data['index_data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			if ( $data['data']['unavailable_message'] || '' === $data['data']['unavailable_message'] ) {
				$unavailable_message = null;
			} else {
				$unavailable_message = $data['data']['unavailable_message'];
			}

			// update data
			$result = $this->wpdb->update(
				$this->node_table_name,
				array(
					'data'                => wp_json_encode( $data['profile_data'] ),
					'last_updated'        => $data['index_data']['last_updated'],
					'is_available'        => $data['data']['is_available'] ?? true,
					'unavailable_message' => $unavailable_message,
					'has_authority'       => $data['data']['has_authority'] ?? true,
				),
				array(
					'profile_url' => $data['index_data']['profile_url'],
					'map_id'      => $data['data']['map_id'],
				)
			);

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
			$result = $this->wpdb->delete(
				$this->node_table_name,
				array(
					'id' => $node_id,
				)
			);

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
			$has_authority = isset( $data['data']['has_authority'] ) && '' !== $data['data']['has_authority'] ? $data['data']['has_authority'] : true;

			if ( 'dismiss' === $data['data']['status'] || 'ignore' === $data['data']['status'] ) {
				$result = $this->wpdb->update(
					$this->node_table_name,
					array(
						'status'        => $data['data']['status'],
						'has_authority' => $has_authority,
						'post_id'       => null,
					),
					array(
						'profile_url' => $data['index_data']['profile_url'],
						'map_id'      => $data['data']['map_id'],
					)
				);
			} else {
				$result = $this->wpdb->update(
					$this->node_table_name,
					array(
						'status'        => $data['data']['status'],
						'has_authority' => $has_authority,
					),
					array(
						'profile_url' => $data['index_data']['profile_url'],
						'map_id'      => $data['data']['map_id'],
					)
				);
			}

			if ( false === $result ) {
				return new WP_Error( 'node_status update_failed', 'Failed to update node status.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node status updated successfully.' );
		}

		public function put_node_authority( $request ): WP_REST_Response|WP_Error {
			$node_id = $request->get_param( 'node_id' );
			$data    = $request->get_json_params();

			// validate data
			if ( ! isset( $node_id ) || ! isset( $data['has_authority'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			$result = $this->wpdb->update(
				$this->node_table_name,
				array(
					'has_authority' => $data['has_authority'],
				),
				array(
					'id' => $node_id,
				)
			);

			if ( false === $result ) {
				return new WP_Error( 'node_authority_update_failed', 'Failed to update node authority.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( 'Node authority updated successfully.' );
		}

		public function post_node( $request ): WP_REST_Response|WP_Error {
			$data = $request->get_json_params();

			// validate data
			if ( ! isset( $data['profile_data'] ) || ! isset( $data['data'] ) || ! isset( $data['index_data'] ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			if ( strlen( $data['index_data']['profile_url'] ) > 2000 ) {
				return new WP_Error( 'profile_url_length_exceeded', 'profile_url is too long.', array( 'status' => 400 ) );
			}

			// insert data
			$result = $this->wpdb->insert(
				$this->node_table_name,
				array(
					'profile_url'         => $data['index_data']['profile_url'],
					'map_id'              => $data['data']['map_id'],
					'data'                => wp_json_encode( $data['profile_data'] ),
					'last_updated'        => $data['index_data']['last_updated'],
					'status'              => $data['data']['status'] ?? 'new',
					'is_available'        => $data['data']['is_available'] ?? true,
					'unavailable_message' => $data['data']['unavailable_message'] ?? null,
					'has_authority'       => $data['data']['has_authority'] ?? true,
				)
			);

			if ( ! $result ) {
				return new WP_Error( 'node_creation_failed', 'Failed to create node.', array( 'status' => 500 ) );
			}

			$inserted_id = $this->wpdb->insert_id;

			$response = array(
				'node_id' => strval( $inserted_id ),
			);

			return rest_ensure_response( $response );
		}

		public function get_proxy( $request ): WP_REST_Response|WP_Error {
			$url = $request->get_param( 'url' );

			if ( ! isset( $url ) ) {
				return new WP_Error( 'invalid_data', 'Invalid data provided', array( 'status' => 400 ) );
			}

			$response = wp_remote_get( $url, array( 'sslverify' => false ) );

			if ( wp_remote_retrieve_response_code( $response ) === 404 ) {
				return new WP_Error( 'proxy_failed', 'Failed to get data from the url.', array( 'status' => 404 ) );
			}

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'proxy_failed', 'Failed to get data from the url.', array( 'status' => 500 ) );
			}

			return rest_ensure_response( json_decode( $response['body'] ) );
		}
	}
}
