<?php

if ( ! class_exists( 'Murmurations_Aggregator_Shortcode' ) ) {
	class Murmurations_Aggregator_Shortcode {
		public function __construct() {
			add_shortcode( 'murmurations_map', array( $this, 'murmurations_map' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_shortcode( 'murmurations_data', array( $this, 'murmurations_data' ) );
		}

		public function murmurations_map( $atts ): string {
			if ( empty( $atts['tag_slug'] ) ) {
				return '<div class="text-center font-bold">Please provide a tag_slug in your shortcode.</div>';
			}

			$view      = isset( $atts['view'] ) ? esc_attr( $atts['view'] ) : 'map';
			$height    = isset( $atts['height'] ) ? esc_attr( $atts['height'] ) : '60';
			$width     = isset( $atts['width'] ) ? esc_attr( $atts['width'] ) : '100';
			$link_type = isset( $atts['link_type'] ) ? esc_attr( $atts['link_type'] ) : 'primary';
			$page_size = isset( $atts['page_size'] ) ? esc_attr( $atts['page_size'] ) : '10';

			return '<div id="wp-map-plugin-page-root" data-tag-slug="' . esc_attr( $atts['tag_slug'] ) . '" data-view="' . $view . '" data-height="' . $height . '" data-width="' . $width . '" data-link-type="' . $link_type . '" data-page-size="' . $page_size . '"></div>';
		}

		public function enqueue_assets(): void {
			$script      = 'admin/assets/map/index.js';
			$script_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $script;

			if ( file_exists( $script_file ) ) {
				wp_enqueue_script( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $script, array(), filemtime( $script_file ), true );
			}

			$style      = 'admin/assets/map/index.css';
			$style_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $style;

			if ( file_exists( $style_file ) ) {
				wp_enqueue_style( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $style, array(), filemtime( $style_file ) );
			}

			wp_localize_script( 'murmurations-aggregator', 'murmurations_aggregator', array(
				'wordpress_url' => get_site_url(),
			) );
		}

		public function murmurations_data( $atts ): string {
			global $wpdb, $post;
			$post_id = $post->ID;

			$attributes = shortcode_atts( array(
				'path' => 'default_path'
			), $atts );

			$json_path = $attributes['path'];

			$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM $table_name WHERE post_id = %d", $post_id ) );

			if ( ! empty( $results ) ) {
				$json_data = $results[0]->data;
				$data      = json_decode( $json_data, true );

				$output = $this->get_json_value_by_path( $json_path, $data );

				return ! is_null( $output ) ? esc_html( $output ) : 'Data not found for the specified path.';
			}

			return 'Post is not found.';
		}

		private function get_json_value_by_path( $path, $data ) {
			$path_parts = preg_split( '/\./', $path, - 1, PREG_SPLIT_NO_EMPTY );

			foreach ( $path_parts as $part ) {
				if ( isset( $data[ $part ] ) ) {
					$data = $data[ $part ];
				} else {
					return null;
				}
			}

			return $data;
		}
	}
}