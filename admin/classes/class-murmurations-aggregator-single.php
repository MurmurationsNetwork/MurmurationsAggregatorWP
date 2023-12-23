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
							$content = $this->organization_schema( $content, $data );
							break;
						case 'people_schema-v0.1.0':
							$content = $this->person_schema( $content, $data );
							break;
						case 'offers_wants_prototype-v0.0.2':
							$content = $this->offer_want_schema( $content, $data );
							break;
					}
				}
			}

			return $content;
		}

		private function organization_schema( $content, $data ): string {
			$name = Murmurations_Aggregator_Utils::get_json_value_by_path( 'name', $data );
			if ( ! is_null( $name ) ) {
				$content .= '<div>Name: ' . $name . '</div>';
			}

			$nickname = Murmurations_Aggregator_Utils::get_json_value_by_path( 'nickname', $data );
			if ( ! is_null( $nickname ) ) {
				$content .= '<div>NickName: ' . $nickname . '</div>';
			}

			$primary_url = Murmurations_Aggregator_Utils::get_json_value_by_path( 'primary_url', $data );
			if ( ! is_null( $primary_url ) ) {
				$content .= '<div>Primary URL: <a href="' . $primary_url . '">' . $primary_url . '</a></div>';
			}

			$tags = Murmurations_Aggregator_Utils::get_json_value_by_path( 'tags', $data );
			if ( ! is_null( $tags ) ) {
				$tags_size = sizeof( $tags );
				$content   .= '<div>Tags: ';
				for ( $i = 0; $i < $tags_size; $i ++ ) {
					if ( $i == $tags_size - 1 ) {
						$content .= '<span>' . $tags[ $i ] . '</span>';
					} else {
						$content .= '<span>' . $tags[ $i ] . '</span>, ';
					}
				}
				$content .= '</div>';
			}

			$urls = Murmurations_Aggregator_Utils::get_json_value_by_path( 'urls', $data );
			if ( ! is_null( $urls ) ) {
				$urls_size = sizeof( $urls );
				$content   .= '<div>URLs: ';
				for ( $i = 0; $i < $urls_size; $i ++ ) {
					$url       = $urls[ $i ];
					$url_name  = $url['name'];
					$url_value = $url['url'];
					if ( ! is_null( $url_name ) && ! is_null( $url_value ) ) {
						if ( $i == $urls_size - 1 ) {
							$content .= '<span>' . $url_name . ': <a href="' . $url_value . '">' . $url_value . '</a></span>';
						} else {
							$content .= '<span>' . $url_name . ': <a href="' . $url_value . '">' . $url_value . '</a></span>, ';
						}
					}
				}
				$content .= '</div>';
			}

			$description = Murmurations_Aggregator_Utils::get_json_value_by_path( 'description', $data );
			if ( ! is_null( $description ) ) {
				$content .= '<div>Description: ' . $description . '</div>';
			}

			return $content;
		}

		private function person_schema( $content, $data ): string {
			$name = Murmurations_Aggregator_Utils::get_json_value_by_path( 'name', $data );
			if ( ! is_null( $name ) ) {
				$content .= '<div>Name: ' . $name . '</div>';
			}

			return $content;
		}

		private function offer_want_schema( $content, $data ): string {
			$name = Murmurations_Aggregator_Utils::get_json_value_by_path( 'name', $data );
			if ( ! is_null( $name ) ) {
				$content .= '<div>Name: ' . $name . '</div>';
			}

			return $content;
		}
	}
}
