<?php

if ( ! class_exists( 'Murmurations_Aggregator_Excerpt' ) ) {
	class Murmurations_Aggregator_Excerpt {
		public function __construct() {
			add_filter( 'get_the_excerpt', array( $this, 'show_shortcode_in_excerpt' ) );
		}

		public function show_shortcode_in_excerpt( $excerpt ): string {
			global $post;
			if ( get_post_type( $post ) == MURMURATIONS_AGGREGATOR_POST_TYPE ) {
				return do_shortcode( $excerpt );
			}

			return $excerpt;
		}
	}
}
