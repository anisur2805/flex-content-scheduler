<?php
/**
 * Uninstall Flex Content Scheduler.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

delete_option( 'flex_cs_version' );
delete_option( 'flex_cs_settings' );

if ( class_exists( 'Anisur\\ContentScheduler\\Database\\ScheduleTable' ) ) {
	$table = new Anisur\ContentScheduler\Database\ScheduleTable();
	$table->drop_table();
}
