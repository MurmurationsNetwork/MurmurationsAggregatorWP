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

					$keyValueMap = [];
					$this->iterateObject( $data, '', $keyValueMap );

					foreach ( $keyValueMap as $key => $value ) {
						$content .= '<div>' . $key . ': ' . $value . '</div>';
					}
				}
			}

			return $content;
		}

		private function iterateObject( $obj, $currentKey = '', &$keyValueMap = [] ): void {
			foreach ( $obj as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$this->iterateObject( $value, $currentKey . $key . '.', $keyValueMap );
				} else {
					$keyValueMap[ $currentKey . $key ] = $value;
				}
			}
		}
	}
}
