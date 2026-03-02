<?php
/**
 * Plugin activation handler.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler;

use Anisur\ContentScheduler\Database\ScheduleTable;

/**
 * Class Activator
 *
 * Handles plugin activation tasks: database setup, cron scheduling, and default options.
 *
 * @since 1.0.0
 */
class Activator {
	/**
	 * Add custom cron interval for every minute.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public static function add_every_5_minutes_schedule( array $schedules ): array {
		$schedules['every_5_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'flex-content-scheduler' ),
		);
		return $schedules;
	}

	/**
	 * Run plugin activation tasks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		global $wp_version;

		if ( version_compare( PHP_VERSION, '7.4', '<' ) || version_compare( (string) $wp_version, '6.0', '<' ) ) {
			deactivate_plugins( plugin_basename( FLEX_CS_PLUGIN_FILE ) );
			wp_die( esc_html__( 'Flex Content Scheduler requires WordPress 6.0+ and PHP 7.4+.', 'flex-content-scheduler' ) );
		}

		$table = new ScheduleTable();
		$table->create_table();

		add_filter( 'cron_schedules', array( __CLASS__, 'add_every_5_minutes_schedule' ) );
		if ( ! wp_next_scheduled( 'flex_cs_process_schedules' ) ) {
			wp_schedule_event( time(), 'every_5_minutes', 'flex_cs_process_schedules' );
		}
		remove_filter( 'cron_schedules', array( __CLASS__, 'add_every_5_minutes_schedule' ) );

		flush_rewrite_rules();
		update_option( 'flex_cs_version', FLEX_CS_VERSION );
		if ( false === get_option( 'flex_cs_settings', false ) ) {
			update_option(
				'flex_cs_settings',
				array(
					'default_action'     => 'unpublish',
					'cron_enabled'       => true,
					'notification_email' => '',
					'allowed_redirect_hosts' => array(),
				)
			);
		}
	}
}
