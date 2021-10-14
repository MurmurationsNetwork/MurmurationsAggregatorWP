<?php

/**
 * Plugin Name:       Murmurations Aggregator
 * Plugin URI:        murmurations.network
 * Description:       Collect and display data from the Murmurations network
 * Version:           0.1.0-alpha
 * Author:            A. McKenty / Photosynthesis
 * Author URI:        Photosynthesis.ca
 * License:           Peer Production License
 * License URI:
 * Text Domain:       murmurations
 */

namespace Murmurations\Aggregator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require plugin_dir_path( __FILE__ ) . 'includes/aggregator.class.php';

define( 'MURMAG_ROOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'MURMAG_ROOT_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function() {
		Aggregator::init();
	}
);

add_action(
  'wp_ajax_save_settings',
  array(
    'Murmurations\Aggregator\Admin',
    'ajax_save_settings'
  )
);
