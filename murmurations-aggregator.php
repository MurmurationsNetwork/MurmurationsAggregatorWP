<?php
/**
 * Plugin Name:       Murmurations Collaborative Map Builder
 * Plugin URI:        https://github.com/MurmurationsNetwork/MurmurationsAggregatorWP
 * Description:       Collect and display data from the Murmurations network.
 * Version:           1.0.0-beta.6
 * Requires at least: 6.4
 * Text Domain:       murmurations-aggregator
 * Author:            Murmurations Network
 * Author URI:        https://murmurations.network
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

/*
The Murmurations Collaborative Map Builder plugin is free software: you can
redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation, either version 3 of
the License, or any later version.

Murmurations Collaborative Map Builder is distributed in the hope that it will
be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
Public License for more details.

You should have received a copy of the GNU General Public License along with
the Murmurations Collaborative Map Builder plugin. If not, see
https://www.gnu.org/licenses/gpl-3.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MurmurationsAggregator' ) ) {
	define( 'MURMURATIONS_AGGREGATOR_VERSION', '1.0.0-beta.6' );
	define( 'MURMURATIONS_AGGREGATOR_URL', plugin_dir_url( __FILE__ ) );
	define( 'MURMURATIONS_AGGREGATOR_DIR', __DIR__ );
	define( 'MURMURATIONS_AGGREGATOR_TABLE', 'murmurations_maps' );
	define( 'MURMURATIONS_AGGREGATOR_NODE_TABLE', 'murmurations_nodes' );
	define( 'MURMURATIONS_AGGREGATOR_POST_TYPE', 'murmurations_node' );
	define( 'MURMURATIONS_AGGREGATOR_TAG_TAXONOMY', 'murmurations_node_tags' );
	define( 'MURMURATIONS_AGGREGATOR_CATEGORY_TAXONOMY', 'murmurations_node_categories' );

	class MurmurationsAggregator {
		public function __construct() {
			$this->register_autoloads();
			$this->register_admin_page();
			$this->register_upgrade();
			$this->register_custom_post();
			$this->register_api();
			$this->register_shortcode();
			$this->register_single();
			$this->register_excerpt();
		}

		private function register_autoloads(): void {
			spl_autoload_register(
				function ( $name ) {
					$name = strtolower( $name );
					$name = str_replace( '_', '-', $name );
					$name = 'class-' . $name;
					$file = __DIR__ . '/admin/classes/' . $name . '.php';

					if ( file_exists( $file ) ) {
							require_once $file;
					}
				}
			);
		}

		public function register_admin_page(): void {
			new Murmurations_Aggregator_Admin_Page();
		}

		public function register_upgrade(): void {
			new Murmurations_Aggregator_Upgrade();
		}

		public function register_custom_post(): void {
			new Murmurations_Aggregator_Custom_Post();
		}

		public function register_api(): void {
			new Murmurations_Aggregator_API();
		}

		public function register_shortcode(): void {
			new Murmurations_Aggregator_Shortcode();
		}

		public function register_single(): void {
			new Murmurations_Aggregator_Single();
		}

		public function register_excerpt(): void {
			new Murmurations_Aggregator_Excerpt();
		}
	}

	new MurmurationsAggregator();
}

if ( class_exists( 'Murmurations_Aggregator_Activation' ) ) {
	register_activation_hook( __FILE__, array( 'Murmurations_Aggregator_Activation', 'activate' ) );
}

if ( class_exists( 'Murmurations_Aggregator_Deactivation' ) ) {
	register_deactivation_hook( __FILE__, array( 'Murmurations_Aggregator_Deactivation', 'deactivate' ) );
}

if ( class_exists( 'Murmurations_Aggregator_Uninstall' ) ) {
	register_uninstall_hook( __FILE__, array( 'Murmurations_Aggregator_Uninstall', 'uninstall' ) );
}
