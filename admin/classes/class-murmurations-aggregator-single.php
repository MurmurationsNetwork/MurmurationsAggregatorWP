<?php

if ( ! class_exists( 'Murmurations_Aggregator_Single' ) ) {
	class Murmurations_Aggregator_Single {
		private array $custom_templates;

		public function __construct() {
			$this->custom_templates = [
				'organizations_schema-v1.0.0'   => 'single-organization_schema.php',
				'people_schema-v0.1.0'          => 'single-people_schema.php',
				'offers_wants_prototype-v0.0.2' => 'single-offers_wants_prototype.php'
			];
			add_filter( 'template_include', array( $this, 'murmurations_aggregator_template_include' ) );
			add_action( 'init', array( $this, 'check_and_disable_filter' ) );
		}

		public function check_and_disable_filter(): void {
			if (current_theme_supports('block-templates')) {
				remove_filter('template_include', array($this, 'murmurations_aggregator_template_include'));
			}
		}

		public function murmurations_aggregator_template_include( $template ) {
			if ( is_single() && get_post_type() == 'murmurations_node' ) {
				global $wpdb;
				$post_id    = get_the_ID();
				$table_name = $wpdb->prefix . MURMURATIONS_AGGREGATOR_NODE_TABLE;

				$results = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM $table_name WHERE post_id = %d", $post_id ) );

				if ( ! empty( $results ) ) {
					$json_data = $results[0]->data;
					$data      = json_decode( $json_data, true );

					$schema = Murmurations_Aggregator_Utils::get_json_value_by_path( 'linked_schemas.0', $data );

					if ( array_key_exists( $schema, $this->custom_templates ) ) {
						$template = locate_template( $this->custom_templates[ $schema ] );
					}
				}
			}

			return $template;
		}
	}
}
