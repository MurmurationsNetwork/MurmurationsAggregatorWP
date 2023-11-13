<?php

if ( ! class_exists( 'Murmurations_Aggregator_Deactivation' ) ) {
	class Murmurations_Aggregator_Deactivation {
		public static function deactivate(): void {
			// Unregister the post type, so the rules are no longer in memory.
			unregister_post_type( 'murmurations_node' );
			unregister_taxonomy( 'murmurations_node_tags' );
			unregister_taxonomy( 'murmurations_node_categories' );
			// Clear the permalinks to remove our post type's rules from the database.
			flush_rewrite_rules();
		}
	}
}
