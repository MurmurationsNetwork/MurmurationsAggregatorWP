<?php

if ( ! class_exists( 'Murmurations_Aggregator_Deactivation' ) ) {
	class Murmurations_Aggregator_Deactivation {
		public static function deactivate(): void {
			// Unregister the post type, so the rules are no longer in memory.
			unregister_post_type( MURMURATIONS_AGGREGATOR_POST_TYPE );
			unregister_taxonomy( MURMURATIONS_AGGREGATOR_TAG_TAXONOMY );
			unregister_taxonomy( MURMURATIONS_AGGREGATOR_CATEGORY_TAXONOMY );
			// Clear the permalinks to remove our post type's rules from the database.
			flush_rewrite_rules();
		}
	}
}
