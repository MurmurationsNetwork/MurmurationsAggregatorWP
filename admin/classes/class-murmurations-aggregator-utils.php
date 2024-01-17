<?php

if ( ! class_exists( 'Murmurations_Aggregator_Utils' ) ) {
	class Murmurations_Aggregator_Utils {
		public static function get_json_value_by_path( $path, $data ) {
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

		public static function get_custom_template( $key ): ?string {
			$custom_templates = array(
				'organizations_schema-v1.0.0' => 'single-organization-schema',
				'people_schema-v0.1.0'        => 'single-people-schema',
				'offers_wants_schema-v0.1.0'  => 'single-offers-wants-prototype-schema',
			);

			if ( array_key_exists( $key, $custom_templates ) ) {
				return $custom_templates[ $key ];
			}

			return null;
		}
	}
}
