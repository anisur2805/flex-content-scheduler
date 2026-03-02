<?php
/**
 * Lightweight logger helper.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Helpers;

/**
 * Class Logger
 *
 * Provides centralized debug/error logging for the plugin.
 *
 * @since 1.0.0
 */
class Logger {
	/**
	 * Log a debug message when WP_DEBUG is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	public static function debug( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[FLEX_CS] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Log an error message unconditionally.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @return void
	 */
	public static function error( string $message ): void {
		error_log( '[FLEX_CS] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
