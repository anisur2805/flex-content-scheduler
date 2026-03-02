<?php
/**
 * Uninstall Flex Content Scheduler.
 *
 * Removes plugin options and drops the custom database table.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
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
	$flex_cs_table = new Anisur\ContentScheduler\Database\ScheduleTable();
	$flex_cs_table->drop_table();
}
