<?php

/**
 * Plugin Name:       Murmurations Aggregator
 * Plugin URI:        murmurations.network
 * Description:       Collect and display data from the Murmurations network
 * Version:           1.0.0
 * Author:            A. McKenty / Photosynthesis
 * Author URI:        Photosynthesis.ca
 * License:           Peer Production License
 * License URI:       
 * Text Domain:       murmurations
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/* Include the environment and core classes */
require plugin_dir_path( __FILE__ ) . 'murmurations-aggregator.class.php';
require plugin_dir_path( __FILE__ ) . 'murmurations-aggregator-wp.class.php';

function activate_murmurations_agg() {
   $env = new Murmurations_Aggregator_WP();
   $env->activate();
}

function deactivate_murmurations_agg() {
  $env = new Murmurations_Aggregator_WP();
  $env->deactivate();
}

register_activation_hook( __FILE__, 'activate_murmurations_agg' );
register_deactivation_hook( __FILE__, 'deactivate_murmurations_agg' );


add_action('wp_footer', 'murms_flush_log');
add_action('admin_footer', 'murms_flush_log');


$murmagg = new Murmurations_Aggregator();

add_shortcode('murmurations-directory', array($murmagg, 'showDirectory'));
add_shortcode('murmurations-map', array($murmagg, 'showMap'));
add_shortcode('murmurations-feeds', array($murmagg, 'showFeeds'));


function murmurations_register_node_post_type()
{
    register_post_type('murmurations_node',
                       array(
                           'labels'      => array(
                               'name'          => __('Nodes'),
                               'singular_name' => __('Node'),
                           ),
                           'public'      => true,
                           'has_archive' => true,
                           'rewrite'     => array( 'slug' => 'nodes' ), //TODO: This should be a setting, so aggregator sites can set the slug prefix. This also means we want to move this into the environment class, so we can access stuff from there (but there's the small matter of how to get that instantiated from the main file and call this without having to pass environment-specific information in the core class)
                       )
    );
}
