<?php
/**
 * Plugin Name:       Flex Content Scheduler
 * Plugin URI:        https://github.com/anisur2805/flex-content-scheduler
 * Description:       Schedule post expiry and visibility changes with flexible date/time rules.
 * Version:           1.0.0
 * Author:            Anisur Rahman
 * Author URI:        https://github.com/anisur2805
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       flex-content-scheduler
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package Flex_Content_Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEX_CS_VERSION', '1.0.0' );
define( 'FLEX_CS_PLUGIN_FILE', __FILE__ );
define( 'FLEX_CS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLEX_CS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FLEX_CS_PLUGIN_SLUG', 'flex-content-scheduler' );

if ( file_exists( FLEX_CS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once FLEX_CS_PLUGIN_DIR . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, array( \Anisur\ContentScheduler\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \Anisur\ContentScheduler\Deactivator::class, 'deactivate' ) );

/**
 * Run the plugin.
 *
 * @since 1.0.0
 *
 * @return void
 */
function flex_cs_run(): void {
	$plugin = new \Anisur\ContentScheduler\Plugin();
	$plugin->run();
}

flex_cs_run();
