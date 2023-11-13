<?php

if ( ! class_exists( 'Murmurations_Aggregator_Custom_Post' ) ) {
	class Murmurations_Aggregator_Custom_Post {
		public function __construct() {
			add_action( 'save_post', array( $this, 'murmurations_nodes_update_status' ) );
			add_action( 'before_delete_post', array( $this, 'murmurations_nodes_delete' ) );
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
					'status' => 'deleted',
					'post_id' => null,
				),
				array(
					'post_id' => $post_id,
				)
			);
		}
	}
}
