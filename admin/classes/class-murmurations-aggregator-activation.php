<?php

if ( ! class_exists('Murmurations_Aggregator_Activation') ) {
	class Murmurations_Aggregator_Activation {
		public static function activate() {
			// set plugin version for future DB upgrade
			$current_version = '1.0.0';
			update_option( 'murmurations_aggregator_version', $current_version );

			global $wpdb;
			$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
		        id INT NOT NULL AUTO_INCREMENT,
		        name VARCHAR(100) NOT NULL,
		        index_url VARCHAR(100) NOT NULL,
		        query_url VARCHAR(100) NOT NULL,
		        tag_slug VARCHAR(100) NOT NULL,
		        map_center POINT NOT NULL,
		        map_scale INT DEFAULT 5 NOT NULL,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				deleted_at TIMESTAMP NULL,
		        PRIMARY KEY (id)
		    ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
}
