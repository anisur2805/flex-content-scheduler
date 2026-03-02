<?php
/**
 * Plugin deactivation handler.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks: clears scheduled cron events.
 *
 * @since 1.0.0
 */
class Deactivator {
	/**
	 * Run plugin deactivation tasks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'flex_cs_process_schedules' );
		flush_rewrite_rules();
	}
}
