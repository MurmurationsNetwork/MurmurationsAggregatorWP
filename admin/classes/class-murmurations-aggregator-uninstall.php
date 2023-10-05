<?php

if ( ! class_exists( 'Murmurations_Node_Uninstall' ) ) {
	class Murmurations_Node_Uninstall {
		public static function uninstall() {
			global $wpdb;
			$table_name      = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;
			$node_table_name = $wpdb->prefix . MURMURATIONS_NODE_TABLE;

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$node_table_name'" ) === $node_table_name ) {
				$wpdb->query( "DROP TABLE IF EXISTS $node_table_name" );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
				$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
			}
		}
	}
}
