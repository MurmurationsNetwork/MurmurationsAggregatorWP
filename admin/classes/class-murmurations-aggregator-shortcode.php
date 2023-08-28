<?php

if ( ! class_exists( 'Murmurations_Aggregator_Shortcode' ) ) {
	class Murmurations_Aggregator_Shortcode {
		public function __construct() {
			add_shortcode( 'murmurations_map', array( $this, 'murmurations_map' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		public function murmurations_map( $atts ) {
			if ( empty( $atts['tag_slug'] ) ) {
				return '<div class="text-center font-bold">Please provide a tag_slug in your shortcode.</div>';
			}

			return '<div id="wp-map-plugin-page-root" data-tag-slug="' . esc_attr( $atts['tag_slug'] ) . '"></div>';
		}

		public function enqueue_assets() {
			$script      = 'admin/assets/map.js';
			$script_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $script;

			if ( file_exists( $script_file ) ) {
				wp_enqueue_script( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $script, array(), filemtime( $script_file ), true );
			}

			$style      = 'admin/assets/map.css';
			$style_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $style;

			if ( file_exists( $style_file ) ) {
				wp_enqueue_style( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $style, array(), filemtime( $style_file ) );
			}
		}
	}
}