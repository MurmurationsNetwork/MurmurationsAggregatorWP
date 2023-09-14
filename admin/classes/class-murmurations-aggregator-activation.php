<?php

if ( ! class_exists( 'Murmurations_Aggregator_Activation' ) ) {
	class Murmurations_Aggregator_Activation {
		public static function activate() {
			// force flush rewrite rules
			flush_rewrite_rules();

			// set plugin version for future DB upgrade
			$current_version = '1.0.0';
			update_option( 'murmurations_aggregator_version', $current_version );

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
			    profile_url VARCHAR(100) NOT NULL,
			    data TEXT NOT NULL,
			    hashed_data VARCHAR(100) NOT NULL,
			    status VARCHAR(100) NOT NULL DEFAULT 'ignored',
			    PRIMARY KEY (id),
			    FOREIGN KEY (map_id) REFERENCES $table_name(id) ON DELETE CASCADE
		    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			dbDelta( $node_sql );
		}
	}
}
