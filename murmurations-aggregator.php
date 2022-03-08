<?php

/**
 * Plugin Name:       Murmurations Aggregator
 * Plugin URI:        https://murmurations.network
 * Description:       Collect and display data from the Murmurations network
 * Version:           0.2.0
 * Author:            A. McKenty / Photosynthesis
 * Author URI:        Photosynthesis.ca
 * License:           GPLv3
 * License URI:				https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       murmurations
 * GitHub Plugin URI: https://github.com/MurmurationsNetwork/MurmurationsAggregatorWP
 */

/*
The Murmurations Aggregator plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Murmurations Aggregator is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with the Murmurations Aggregator plugin. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/

namespace Murmurations\Aggregator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require plugin_dir_path( __FILE__ ) . 'includes/class-aggregator.php';

define( 'MURMAG_ROOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'MURMAG_ROOT_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function() {
		Aggregator::init();
	}
);
