<?php

if ( ! class_exists( 'Murmurations_Aggregator_Admin_Page' ) ) {
    class Murmurations_Aggregator_Admin_Page {
	    public function __construct() {
		    add_action( 'admin_menu', array( $this, 'add_menus' ) );
		    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	    }

	    public function add_menus() {
		    add_menu_page(
			    'Murmurations Maps',
			    'Murm-Maps',
			    'edit_posts', // capability
			    'murmurations-aggregator',
			    array( $this, 'admin_page' ),
			    'dashicons-networking', // icon URL
			    null // position
		    );
	    }

	    public function admin_page() {
		    echo '<div id="wp-admin-plugin-page-root"></div>';
	    }

	    public function enqueue_assets( $hook ) {
		    if ( 'toplevel_page_murmurations-aggregator' !== $hook ) {
			    return;
		    }

		    $script      = 'admin/assets/bundle.js';
		    $script_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $script;

		    if ( file_exists( $script_file ) ) {
			    wp_enqueue_script( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $script, array(), filemtime( $script_file ), true );
		    }

		    $style      = 'admin/assets/bundle.css';
		    $style_file = MURMURATIONS_AGGREGATOR_DIR . '/' . $style;

		    if ( file_exists( $style_file ) ) {
			    wp_enqueue_style( 'murmurations-aggregator', MURMURATIONS_AGGREGATOR_URL . $style, array(), filemtime( $style_file ) );
		    }

		    // add site url to script
		    wp_localize_script( 'murmurations-aggregator', 'murmurations_aggregator', array(
			    'wordpress_url' => get_site_url(),
			    'wp_nonce' => wp_create_nonce( 'wp_rest' ),
		    ) );
	    }
    }
}
