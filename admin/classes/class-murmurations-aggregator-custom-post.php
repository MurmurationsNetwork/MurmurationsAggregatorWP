<?php

if ( ! class_exists( 'Murmurations_Aggregator_Custom_Post' ) ) {
	class Murmurations_Aggregator_Custom_Post {
		public function __construct() {
			add_action( 'init', array( $this, 'create_murmurations_node_post_type' ) );
			add_action( 'init', array( $this, 'create_murmurations_node_taxonomy' ) );
		}

		public function create_murmurations_node_post_type() {
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

		function create_murmurations_node_taxonomy() {
			// Add custom tags
			register_taxonomy(
				'murmurations_node_tags',
				'murmurations_node',
				array(
					'label'        => 'Murmurations Node Tags',
					'hierarchical' => false,
					'rewrite'      => array( 'slug' => 'murmurations-node-tags' ),
				)
			);

			// Add custom categories
			register_taxonomy(
				'murmurations_node_categories',
				'murmurations_node',
				array(
					'label'        => 'Murmurations Node Categories',
					'hierarchical' => true,
					'rewrite'      => array( 'slug' => 'murmurations-node-categories' ),
				)
			);
		}
	}
}
