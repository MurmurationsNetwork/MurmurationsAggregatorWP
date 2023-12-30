<?php

if ( ! class_exists( 'Murmurations_Aggregator_Search_Block' ) ) {
	class Murmurations_Aggregator_Search_Block {
		public function __construct() {
			add_action( 'init', array( $this, 'murmurations_search_block_block_init' ) );
		}

		public function murmurations_search_block_block_init(): void {
			register_block_type( __DIR__ . '/../murmurations-search-block/build' );
		}
	}
}