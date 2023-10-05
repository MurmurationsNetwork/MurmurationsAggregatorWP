<?php

if ( ! class_exists( 'Murmurations_Aggregator_Custom_Post' ) ) {
	class Murmurations_Aggregator_Custom_Post {
		public function __construct() {
			add_action( 'init', array( $this, 'create_murmurations_node_post_type' ) );
			add_action( 'init', array( $this, 'create_murmurations_node_taxonomy' ) );
			add_action( 'save_post', array( $this, 'murmurations_nodes_update_status' ) );
			add_action( 'before_delete_post', array( $this, 'murmurations_nodes_delete' ) );
		}

		public function create_murmurations_node_post_type(): void {
			$labels = array(
				'name'          => 'Murmurations Nodes',
				'singular_name' => 'Murmurations Node',
				'menu_name'     => 'Murmurations Nodes',
				'add_new'       => 'Add New Murmurations Node',
				'add_new_item'  => 'Add New Murmurations Node',
			);

			$args = array(
				'labels'      => $labels,
				'public'      => true,
				'has_archive' => true,
				'supports'    => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			);

			register_post_type( 'murmurations_node', $args );
		}

		public function create_murmurations_node_taxonomy(): void {
			// Add custom tags
			register_taxonomy(
				'murmurations_node_tags',
				'murmurations_node',
				array(
					'label'             => 'Murmurations Node Tags',
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => 'murmurations-node-tags' ),
				)
			);

			// Add custom categories
			register_taxonomy(
				'murmurations_node_categories',
				'murmurations_node',
				array(
					'label'             => 'Murmurations Node Categories',
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => 'murmurations-node-categories' ),
				)
			);
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
