<?php
/**
 * Date/time utility helpers.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Helpers;

/**
 * Class DateTimeHelper
 *
 * Provides static helper methods for date/time comparisons and conversions.
 *
 * @since 1.0.0
 */
class DateTimeHelper {
	/**
	 * Check whether a UTC datetime string is in the past.
	 *
	 * @since 1.0.0
	 *
	 * @param string $utc_datetime UTC datetime string (Y-m-d H:i:s).
	 * @return bool True if the datetime is in the past.
	 */
	public static function is_past( string $utc_datetime ): bool {
		$now = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		$dt  = new \DateTime( $utc_datetime, new \DateTimeZone( 'UTC' ) );

		return $dt <= $now;
	}

	/**
	 * Format a UTC datetime for display in a given timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $utc_datetime UTC datetime string (Y-m-d H:i:s).
	 * @param string $timezone     Optional timezone identifier. Defaults to site timezone.
	 * @return string Formatted datetime string with timezone abbreviation.
	 */
	public static function format_for_display( string $utc_datetime, string $timezone = '' ): string {
		$tz = $timezone ? new \DateTimeZone( $timezone ) : wp_timezone();
		$dt = new \DateTime( $utc_datetime, new \DateTimeZone( 'UTC' ) );
		$dt->setTimezone( $tz );

		return $dt->format( 'Y-m-d H:i:s T' );
	}

	/**
	 * Convert a datetime from a given timezone to UTC.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime Datetime string.
	 * @param string $timezone Source timezone identifier.
	 * @return string UTC datetime string (Y-m-d H:i:s).
	 */
	public static function to_utc( string $datetime, string $timezone ): string {
		$dt = new \DateTime( $datetime, new \DateTimeZone( $timezone ) );
		$dt->setTimezone( new \DateTimeZone( 'UTC' ) );

		return $dt->format( 'Y-m-d H:i:s' );
	}
}
