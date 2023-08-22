<?php

if ( ! class_exists( 'Murmurations_Aggregator_API' ) ) {
	class Murmurations_Aggregator_API {
		private $wpdb;
		private $table_name;

		public function __construct() {
			global $wpdb;
			$this->wpdb       = $wpdb;
			$this->table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;

			add_action( 'rest_api_init', array( $this, 'register_api_routes' ));
		}

		public function register_api_routes() {
			register_rest_route(
				'murmurations-aggregator/v1',
				'/map/(?P<tag_slug>[\w]+)',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_map' ),
				)
			);

			register_rest_route(
				'murmurations-aggregator/v1',
				'/map',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_map' ),
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

			$response = $this->handle_response( $result, 'Map created successfully.', 'Failed to create a map.' );

			return rest_ensure_response( $response );
		}

		private function handle_response( $update_result, $message, $error_message ) {
			if ( $update_result === false ) {
				return new WP_Error( 'update_failed', esc_html__( $error_message, 'text-domain' ), array( 'status' => 500 ) );
			}

			return array(
				'message' => esc_html__( $message, 'text-domain' ),
			);
		}
	}
}