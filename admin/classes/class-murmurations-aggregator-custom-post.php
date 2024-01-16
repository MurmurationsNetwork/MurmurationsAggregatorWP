<?php

if ( ! class_exists( 'Murmurations_Aggregator_Custom_Post' ) ) {
	class Murmurations_Aggregator_Custom_Post {
		public function __construct() {
			add_action( 'save_post', array( $this, 'murmurations_nodes_update_status' ) );
			add_action( 'before_delete_post', array( $this, 'murmurations_nodes_delete' ) );
			add_action( 'add_meta_boxes', array( $this, 'murmurations_nodes_add_meta_boxes' ) );
		}

		public function murmurations_nodes_update_status( $post_id ): void {
			// check if this is an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// check the post type
			if ( 'murmurations_node' != get_post_type( $post_id ) ) {
				return;
			}

			// update node table status
			global $wpdb;
			$node_table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;
			$post_status     = get_post_status( $post_id );

			$wpdb->update(
				$node_table_name,
				array(
					'status' => $post_status,
				),
				array(
					'post_id' => $post_id,
				)
			);
		}

		public function murmurations_nodes_delete( $post_id ): void {
			global $wpdb;
			$node_table = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			// update status from the table
			$wpdb->update(
				$node_table,
				array(
					'status'  => 'deleted',
					'post_id' => null,
				),
				array(
					'post_id' => $post_id,
				)
			);
		}

		public function murmurations_nodes_add_meta_boxes(): void {
			add_meta_box(
				'murmurations_node_meta_box',
				__( 'Murmurations Node', 'murmurations-aggregator' ),
				array( $this, 'murmurations_nodes_display_meta_box' ),
				'murmurations_node',
				'normal',
				'high'
			);
		}

		public function murmurations_nodes_display_meta_box( $post ): void {
			global $wpdb;
			$post_id    = $post->ID;
			$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM {$table_name} WHERE post_id = %d", $post_id ) );

			if ( ! empty( $results ) ) {
				$json_data = $results[0]->data;
				$data      = json_decode( $json_data, true );

				$json_output = json_encode($data, JSON_PRETTY_PRINT);

				echo '<pre>' . esc_html($json_output) . '</pre>';
			} else {
				echo '<p>Data Not Found.</p>';
			}
		}
	}
}
