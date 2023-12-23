<?php

if ( ! class_exists( 'Murmurations_Aggregator_Single' ) ) {
	class Murmurations_Aggregator_Single {
		public function __construct() {
			add_filter( 'the_content', array( $this, 'murmurations_aggregator_template_include' ) );
		}

		public function murmurations_aggregator_template_include( $content ) {
			if ( is_single() && get_post_type() == 'murmurations_node' ) {
				global $wpdb;
				$post_id    = get_the_ID();
				$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM $table_name WHERE post_id = %d", $post_id ) );

				if ( ! empty( $results ) ) {
					$json_data = $results[0]->data;
					$data      = json_decode( $json_data, true );

					$schema = Murmurations_Aggregator_Utils::get_json_value_by_path( 'linked_schemas.0', $data );

					switch ( $schema ) {
						case 'organizations_schema-v1.0.0':
							$content = $this->organization_schema( $content );
							break;
						case 'people_schema-v0.1.0':
							$content = $this->person_schema( $content );
							break;
						case 'offers_wants_prototype-v0.0.2':
							$content = $this->offer_want_schema( $content );
							break;
					}
				}
			}

			return $content;
		}

		private function organization_schema( $content ): string {
			$content          .= '<div>' . do_shortcode( '[murmurations_data path="name"]' ) . '</div>';
			$content          .= '<div>' . do_shortcode( '[murmurations_data path="nickname"]' ) . '</div>';
			$content          .= '<div>' . do_shortcode( '[murmurations_data path="primary_url"]' ) . '</div>';
			$content          .= '<div>' . do_shortcode( '[murmurations_data path="tags.0"]' ) . '</div>';
			$content          .= '<div>' . do_shortcode( '[murmurations_data path="description"]' ) . '</div>';

			return $content;
		}

		private function person_schema( $content ): string {
			$shortcode_output = do_shortcode( '[murmurations_data path="description"]' );
			$content          .= '<div>' . $shortcode_output . '</div>';

			return $content;
		}

		private function offer_want_schema( $content ): string {
			$shortcode_output = do_shortcode( '[murmurations_data path="description"]' );
			$content          .= '<div>' . $shortcode_output . '</div>';

			return $content;
		}
	}
}
