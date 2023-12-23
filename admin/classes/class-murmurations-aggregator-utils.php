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
	}
}