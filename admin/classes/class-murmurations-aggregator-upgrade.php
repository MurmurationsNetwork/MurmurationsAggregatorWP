<?php

if ( ! class_exists( 'Murmurations_Aggregator_Upgrade' ) ) {
	class Murmurations_Aggregator_Upgrade {
		public function __construct() {
			add_action( 'plugin_loaded', array( $this, 'upgrade_db_check' ) );
		}

		public static function upgrade_db_check(): void {
			global $wpdb;
			$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			// check the table is existed or not
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name;

			$current_version = get_option( 'murmurations_aggregator_version' );

			if ( version_compare( $current_version, '1.0.0-beta.1', '<' ) ) {
				if ( $table_exists ) {
					$sql = "ALTER TABLE $table_name
                        ADD COLUMN is_available BOOLEAN NOT NULL DEFAULT 0,
                        ADD COLUMN unavailable_message VARCHAR(255) DEFAULT NULL";

					$wpdb->query( $sql );
				}
			}

			// update the plugin version in the database
			update_option( 'murmurations_aggregator_version', '1.0.0-beta.1' );

			$current_version = get_option( 'murmurations_aggregator_version' );

			if ( version_compare( $current_version, '1.0.0-beta.3', '<' ) ) {
				if ( $table_exists ) {
					$sql = "ALTER TABLE $table_name
                        MODIFY profile_url VARCHAR(2000) NOT NULL";

					$wpdb->query( $sql );
				}
			}

			// update the plugin version in the database
			update_option( 'murmurations_aggregator_version', '1.0.0-beta.3' );

			// next upgrade put here

			// update the latest plugin version to the database
			update_option( 'murmurations_aggregator_version', MURMURATIONS_AGGREGATOR_VERSION );
		}
	}
}