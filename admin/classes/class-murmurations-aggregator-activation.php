<?php

function create_murmurations_node_post_type(): void {
	$labels = array(
		'name'          => 'Murmurations Nodes',
		'singular_name' => 'Murmurations Node',
		'menu_name'     => 'Murm-Nodes',
	);

	$args = array(
		'labels'          => $labels,
		'public'          => true,
		'has_archive'     => true,
		'rewrite'         => array( 'slug' => 'murmurations-node' ),
		'supports'        => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions', 'excerpt' ),
		'capability_type' => 'post',
		'capabilities'    => array(
			'create_posts' => false,
		),
		'map_meta_cap'    => true,
		'show_in_rest'    => true,
	);

	register_post_type( MURMURATIONS_AGGREGATOR_POST_TYPE, $args );
}

function create_murmurations_node_taxonomy(): void {
	// Add custom tags
	register_taxonomy(
		MURMURATIONS_AGGREGATOR_TAG_TAXONOMY,
		MURMURATIONS_AGGREGATOR_POST_TYPE,
		array(
			'label'             => 'Murmurations Node Tags',
			'hierarchical'      => false,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'murmurations-node-tags' ),
			'show_in_rest'      => true,
		)
	);

	// Add custom categories
	register_taxonomy(
		MURMURATIONS_AGGREGATOR_CATEGORY_TAXONOMY,
		MURMURATIONS_AGGREGATOR_POST_TYPE,
		array(
			'label'             => 'Murmurations Node Categories',
			'hierarchical'      => true,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'murmurations-node-categories' ),
			'show_in_rest'      => true,
		)
	);
}

add_action( 'init', 'create_murmurations_node_post_type' );
add_action( 'init', 'create_murmurations_node_taxonomy' );

if ( ! class_exists( 'Murmurations_Aggregator_Activation' ) ) {
	class Murmurations_Aggregator_Activation {
		public static function activate(): void {
			// Trigger our function that registers the custom post type plugin.
			create_murmurations_node_post_type();
			create_murmurations_node_taxonomy();
			// Clear the permalinks after the post type has been registered.
			flush_rewrite_rules();

			// update the plugin version in the database
			update_option( 'murmurations_aggregator_version', MURMURATIONS_AGGREGATOR_VERSION );

			global $wpdb;
			$table_name      = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;
			$node_table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			$charset_collate = $wpdb->get_charset_collate();

			// default map center is France
			$sql = "CREATE TABLE $table_name (
		        id INT NOT NULL AUTO_INCREMENT,
		        name VARCHAR(100) NOT NULL,
		        index_url VARCHAR(100) NOT NULL,
		        query_url VARCHAR(255) NOT NULL,
		        tag_slug VARCHAR(100) NOT NULL UNIQUE,
		        map_center_lat DECIMAL(10, 7) DEFAULT 46.603354 NOT NULL,
		        map_center_lon DECIMAL(10, 7) DEFAULT 1.888334 NOT NULL,
		        map_scale INT DEFAULT 5 NOT NULL,
		        last_updated TIMESTAMP NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				deleted_at TIMESTAMP NULL,
		        PRIMARY KEY (id)
		    ) $charset_collate;";

			// create table for murmurations nodes
			$node_sql = "CREATE TABLE $node_table_name (
		        id INT NOT NULL AUTO_INCREMENT,
			    map_id INT NOT NULL,
			    post_id INT,
			    profile_url VARCHAR(2000) NOT NULL,
			    data TEXT NOT NULL,
			    last_updated VARCHAR(100) NOT NULL,
			    status VARCHAR(100) NOT NULL DEFAULT 'ignored',
			    is_available BOOLEAN NOT NULL DEFAULT 0,
			    unavailable_message VARCHAR(100) DEFAULT NULL,
			    has_authority BOOLEAN NOT NULL DEFAULT 1,
			    PRIMARY KEY (id)
		    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			dbDelta( $node_sql );

			// Add the foreign key constraint
			$foreign_key_sql = "ALTER TABLE $node_table_name 
                        ADD FOREIGN KEY (map_id) 
                        REFERENCES $table_name(id) 
                        ON DELETE CASCADE;";
			$wpdb->query( $foreign_key_sql );
		}
	}
}
