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
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $_SERVER['REQUEST_URI'] ) ) {
    $fcs_request_uri = (string) $_SERVER['REQUEST_URI'];
    if (
        false !== strpos( $fcs_request_uri, '/wp-json/fcs/' ) ||
        false !== strpos( $fcs_request_uri, 'rest_route=/fcs/' ) ||
        false !== strpos( $fcs_request_uri, 'rest_route=%2Ffcs%2F' )
    ) {
        @ini_set( 'display_errors', '0' );
    }
}

define( 'FCS_VERSION', '1.0.0' );
define( 'FCS_PLUGIN_FILE', __FILE__ );
define( 'FCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FCS_PLUGIN_SLUG', 'flex-content-scheduler' );

if ( file_exists( FCS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once FCS_PLUGIN_DIR . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, array( 'Anisur\\ContentScheduler\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Anisur\\ContentScheduler\\Deactivator', 'deactivate' ) );

function fcs_run() {
    $plugin = new \Anisur\ContentScheduler\Plugin();
    $plugin->run();
}

fcs_run();
