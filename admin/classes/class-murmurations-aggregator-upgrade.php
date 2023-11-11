<?php

if ( ! class_exists( 'Murmurations_Aggregator_Upgrade' ) ) {
	class Murmurations_Aggregator_Upgrade {
		public function __construct() {
			add_action( 'upgrader_process_complete', array( $this, 'upgrade_db_check' ) );
		}

		public static function upgrade_db_check( $options ): void {
			if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
				// uncomment the following line to upgrade the db
				$current_version = get_option( 'murmurations_aggregator_version' );

				$new_version = MURMURATIONS_AGGREGATOR_VERSION;

				if ( version_compare( $current_version, $new_version, '<' ) ) {
					// update db
					global $wpdb;
					$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

					$sql = "ALTER TABLE $table_name
                        ADD COLUMN is_available BOOLEAN NOT NULL DEFAULT 0,
                        ADD COLUMN unavailable_message VARCHAR(255) DEFAULT NULL";

					$wpdb->query( $sql );
				}

				// update the plugin version in the database
				update_option( 'murmurations_aggregator_version', $new_version );
			}
		}
	}
}