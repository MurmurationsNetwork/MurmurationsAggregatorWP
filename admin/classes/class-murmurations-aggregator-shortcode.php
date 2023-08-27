<?php

if ( ! class_exists( 'Murmurations_Aggregator_Shortcode' ) ) {
	class Murmurations_Aggregator_Shortcode {
		public function __construct() {
			add_shortcode( 'murmurations_map', array( $this, 'murmurations_map' ) );
		}

		public function murmurations_map( $atts ) {
			return "<p class='text-center'>This is a Murmurations Map shortcode</p>";
		}
	}
}