<?php

if ( ! class_exists( 'Murmurations_Aggregator_Uninstall' ) ) {
	class Murmurations_Aggregator_Uninstall {
		public static function uninstall(): void {
			// clean CPT and taxonomies
			$posts = get_posts(
				array(
					'post_type'   => MURMURATIONS_AGGREGATOR_POST_TYPE,
					'numberposts' => - 1,
				)
			);

			foreach ( $posts as $post ) {
				wp_delete_post( $post->ID, true );
			}

			$taxonomies = array(
				MURMURATIONS_AGGREGATOR_TAG_TAXONOMY,
				MURMURATIONS_AGGREGATOR_CATEGORY_TAXONOMY,
			);

			foreach ( $taxonomies as $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);

				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
			}

			// Unregister the post type, so the rules are no longer in memory.
			unregister_post_type( 'murmurations_node' );
			unregister_taxonomy( 'murmurations_node_tags' );
			unregister_taxonomy( 'murmurations_node_categories' );
			// Clear the permalinks to remove our post type's rules from the database.
			flush_rewrite_rules();

			// clean database
			global $wpdb;
			$table_name      = $wpdb->prefix . MURMURATIONS_AGGREGATOR_TABLE;
			$node_table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$node_table_name'" ) === $node_table_name ) {
				$wpdb->query( "DROP TABLE IF EXISTS $node_table_name" );
			}

			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
				$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
			}

			// clean options
			delete_option( 'murmurations_aggregator_version' );
		}
	}
}
