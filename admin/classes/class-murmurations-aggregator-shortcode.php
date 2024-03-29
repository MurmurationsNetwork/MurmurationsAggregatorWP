<?php

if ( ! class_exists( 'Murmurations_Aggregator_Shortcode' ) ) {
	class Murmurations_Aggregator_Shortcode {
		public function __construct() {
			add_shortcode( 'murmurations_map', array( $this, 'murmurations_map' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_shortcode( 'murmurations_data', array( $this, 'murmurations_data' ) );
			add_shortcode( 'murmurations_image', array( $this, 'murmurations_image' ) );
			add_shortcode( 'murmurations_excerpt', array( $this, 'murmurations_excerpt' ) );
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

			wp_localize_script(
				'murmurations-aggregator',
				'murmurations_aggregator',
				array(
					'wordpress_url' => get_site_url(),
				)
			);
		}

		public function murmurations_data( $atts ): string {
			$attributes = shortcode_atts(
				array(
					'path'        => 'default_path',
					'second_path' => 'second_default_path',
					'title'       => '',
				),
				$atts
			);

			$json_path        = $attributes['path'];
			$second_json_path = $attributes['second_path'];
			$title            = $attributes['title'];
			$data             = $this->get_murmurations_data();

			if ( is_null( $data ) ) {
				return '';
			}

			$output        = Murmurations_Aggregator_Utils::get_json_value_by_path( $json_path, $data );
			$second_output = ! empty( $second_json_path ) ? Murmurations_Aggregator_Utils::get_json_value_by_path( $second_json_path, $data ) : null;

			if ( ! is_null( $output ) || ! is_null( $second_output ) ) {
				$formatted_title = ! empty( $title ) ? "<p>$title: " : '<p>';

				if ( ! is_null( $output ) && ! is_null( $second_output ) ) {
					return $formatted_title . $this->format_output( $output ) . ' - ' . $this->format_output( $second_output ) . '</p>';
				} else {
					$single_output = ! is_null( $output ) ? $output : $second_output;

					return $formatted_title . $this->format_output( $single_output ) . '</p>';
				}
			} else {
				return '';
			}
		}

		public function murmurations_image( $atts ): string {
			$attributes = shortcode_atts(
				array(
					'path'  => 'default_image_path',
					'width' => '200',
				),
				$atts
			);

			$json_path = $attributes['path'];
			$width     = $attributes['width'];
			$data      = $this->get_murmurations_data();

			if ( is_null( $data ) ) {
				return '';
			}

			$image_path = Murmurations_Aggregator_Utils::get_json_value_by_path( $json_path, $data );

			if ( ! is_null( $image_path ) ) {
				$img_tag = "<p><img src='" . esc_url( $image_path ) . "' alt='image'";

				if ( ! empty( $width ) ) {
					$img_tag .= " width='" . intval( $width ) . "'";
				}

				$img_tag .= ' /></p>';

				return $img_tag;
			} else {
				return '';
			}
		}

		public function murmurations_excerpt(): string {
			$data = $this->get_murmurations_data();

			if ( is_null( $data ) ) {
				return '';
			}

			$content = '';
			$schema  = Murmurations_Aggregator_Utils::get_json_value_by_path( 'linked_schemas.0', $data );

			switch ( $schema ) {
				case 'organizations_schema-v1.0.0':
					$content .= do_shortcode( '[murmurations_data path="description"]' ) ? do_shortcode( '[murmurations_data path="description"]' ) . ', ' : '';
					$content  = substr( $content, 0, - 2 );
					break;
				case 'people_schema-v0.1.0':
					$content .= do_shortcode( '[murmurations_data path="name"]' ) ? do_shortcode( '[murmurations_data path="name"]' ) . ', ' : '';
					$content .= do_shortcode( '[murmurations_data path="description"]' ) ? do_shortcode( '[murmurations_data path="description"]' ) . ', ' : '';
					$content  = substr( $content, 0, - 2 );
					break;
				case 'offers_wants_schema-v0.1.0':
					$content .= do_shortcode( '[murmurations_data path="title"]' ) ? do_shortcode( '[murmurations_data path="title"]' ) . ', ' : '';
					$content .= do_shortcode( '[murmurations_data path="exchange_type"]' ) ? do_shortcode( '[murmurations_data path="exchange_type"]' ) . ', ' : '';
					$content .= do_shortcode( '[murmurations_data path="details_url"]' ) ? do_shortcode( '[murmurations_data path="details_url"]' ) . ', ' : '';
					$content  = substr( $content, 0, - 2 );
					break;
			}

			return $content;
		}

		private function get_murmurations_data() {
			global $wpdb, $post;
			if ( ! is_a( $post, 'WP_Post' ) ) {
				return null;
			}

			$post_id = $post->ID;

			$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM $table_name WHERE post_id = %d", $post_id ) );

			if ( empty( $results ) ) {
				return null;
			}

			$json_data = $results[0]->data;

			return json_decode( $json_data, true );
		}

		private function format_output( $output ): string {
			if ( is_array( $output ) ) {
				return $this->format_array_output( $output );
			} else {
				return $this->format_string_output( $output );
			}
		}

		private function format_array_output( array $unformatted_array ): string {
			$html_output = '<ul>';
			foreach ( $unformatted_array as $item ) {
				if ( is_array( $item ) ) {
					// check if associative array (object)
					if ( $this->is_assoc( $item ) ) {
						$html_output .= '<li>';
						foreach ( $item as $key => $value ) {
							$html_output .= $this->format_key_value( $key, $value ) . '<br>';
						}
						$html_output .= '</li>';
					} else {
						$html_output .= '<li>' . $this->format_string_output( $item ) . '</li>';
					}
				} else {
					$html_output .= '<li>' . $this->format_string_output( $item ) . '</li>';
				}
			}
			$html_output .= '</ul>';

			return $html_output;
		}

		private function format_string_output( $unformatted_string ): string {
			if ( $this->is_email( $unformatted_string ) ) {
				return '<a href="mailto:' . esc_html( $unformatted_string ) . '">' . esc_html( $unformatted_string ) . '</a>';
			} elseif ( $this->is_url( $unformatted_string ) ) {
				return '<a target="_blank" href="' . esc_url( $unformatted_string ) . '">' . esc_html( $unformatted_string ) . '</a>';
			} else {
				return esc_html( $unformatted_string );
			}
		}

		private function format_key_value( $key, $value ): string {
			$formatted_value = is_array( $value ) ? $this->format_array_output( $value ) : $this->format_string_output( $value );

			return '<strong>' . esc_html( $key ) . ':</strong> ' . $formatted_value;
		}

		private function is_assoc( array $arr ): bool {
			if ( array() === $arr ) {
				return false;
			}

			return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
		}

		private function is_url( $input_string ): bool {
			return is_string( $input_string ) && ( str_contains( $input_string, 'http' ) || str_contains( $input_string, 'https' ) );
		}

		private function is_email( $input_string ): bool {
			return is_string( $input_string ) && str_contains( $input_string, '@' );
		}
	}
}
