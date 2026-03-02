<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once __DIR__ . '/wp-stubs.php';

if ( ! defined( 'FLEX_CS_VERSION' ) ) {
	define( 'FLEX_CS_VERSION', '1.0.0' );
}

if ( ! defined( 'FLEX_CS_PLUGIN_FILE' ) ) {
	define( 'FLEX_CS_PLUGIN_FILE', dirname( __DIR__ ) . '/flex-content-scheduler.php' );
}

if ( ! defined( 'FLEX_CS_PLUGIN_DIR' ) ) {
	define( 'FLEX_CS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'FLEX_CS_PLUGIN_URL' ) ) {
	define( 'FLEX_CS_PLUGIN_URL', 'https://example.com/wp-content/plugins/flex-content-scheduler/' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/fixtures/' );
}
