<?php
/**
 * Schedule management operations.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Scheduler;

use Anisur\ContentScheduler\Database\ScheduleTable;
use WP_Error;

/**
 * Class ScheduleManager
 *
 * Manages CRUD operations for content expiry schedules.
 *
 * @since 1.0.0
 */
class ScheduleManager {
	/**
	 * Last error encountered.
	 *
	 * @since 1.0.0
	 * @var WP_Error|null
	 */
	private ?WP_Error $last_error = null;

	/**
	 * Create a new schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Schedule data.
	 * @return int|false Schedule ID on success, false on failure.
	 */
	public function create_schedule( array $data ) {
		global $wpdb;

		$this->last_error = null;
		$this->ensure_table_ready();

		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			$this->last_error = $validation;
			return false;
		}

		$sanitized = $this->sanitize( $data );
		$this->remove_existing_pending_schedules( (int) $sanitized['post_id'] );

		/**
		 * Filter schedule data before database insert.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sanitized Sanitized schedule data.
		 */
		$sanitized = apply_filters( 'flex_cs_schedule_data_before_insert', $sanitized );

		if ( 'redirect' !== $sanitized['expiry_action'] ) {
			delete_post_meta( (int) $sanitized['post_id'], '_flex_cs_redirect_url' );
		}

		$suppress_state = $wpdb->suppress_errors( true );
		$result = $wpdb->insert(
			ScheduleTable::get_table_name(),
			$sanitized,
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);
		$wpdb->suppress_errors( $suppress_state );

		if ( false === $result ) {
			$this->last_error = new WP_Error(
				'flex_cs_db_insert_failed',
				! empty( $wpdb->last_error ) ? $wpdb->last_error : __( 'Database insert failed.', 'flex-content-scheduler' )
			);
			if ( false !== stripos( (string) $wpdb->last_error, 'doesn' ) && false !== stripos( (string) $wpdb->last_error, 'exist' ) ) {
				$this->ensure_table_ready( true );
				$suppress_state = $wpdb->suppress_errors( true );
				$result         = $wpdb->insert(
					ScheduleTable::get_table_name(),
					$sanitized,
					array( '%d', '%s', '%s', '%s', '%s', '%d' )
				);
				$wpdb->suppress_errors( $suppress_state );
				if ( false === $result ) {
					$this->last_error = new WP_Error(
						'flex_cs_db_insert_failed',
						! empty( $wpdb->last_error ) ? $wpdb->last_error : __( 'Database insert failed.', 'flex-content-scheduler' )
					);
					return false;
				}
			} else {
				return false;
			}
		}

		if ( false === $result ) {
			return false;
		}

		$id = (int) $wpdb->insert_id;
		/**
		 * Fires after a schedule is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $id        Schedule ID.
		 * @param array $sanitized Sanitized schedule data.
		 */
		do_action( 'flex_cs_schedule_created', $id, $sanitized );
		$this->maybe_schedule_processing_event( (string) $sanitized['expiry_date'] );
		$this->invalidate_schedule_cache( $id, (int) $sanitized['post_id'] );
		$this->bump_count_cache_buster();

		return $id;
	}

	/**
	 * Update an existing schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $schedule_id Schedule ID.
	 * @param array $data        Schedule data.
	 * @return bool True on success, false on failure.
	 */
	public function update_schedule( int $schedule_id, array $data ): bool {
		global $wpdb;

		$this->last_error = null;
		$this->ensure_table_ready();

		$existing = $this->get_schedule( $schedule_id );
		if ( ! $existing ) {
			$this->last_error = new WP_Error( 'flex_cs_schedule_not_found', __( 'Schedule not found.', 'flex-content-scheduler' ) );
			return false;
		}

		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			$this->last_error = $validation;
			return false;
		}

		$sanitized = $this->sanitize( $data );
		$this->remove_existing_pending_schedules( (int) $sanitized['post_id'], $schedule_id );

		if ( 'redirect' !== $sanitized['expiry_action'] ) {
			delete_post_meta( (int) $sanitized['post_id'], '_flex_cs_redirect_url' );
		}

		$suppress_state = $wpdb->suppress_errors( true );
		$result         = $wpdb->update(
			ScheduleTable::get_table_name(),
			$sanitized,
			array( 'id' => $schedule_id ),
			array( '%d', '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);
		$wpdb->suppress_errors( $suppress_state );

		if ( false === $result ) {
			$this->last_error = new WP_Error(
				'flex_cs_db_update_failed',
				! empty( $wpdb->last_error ) ? $wpdb->last_error : __( 'Database update failed.', 'flex-content-scheduler' )
			);
			return false;
		}

		/**
		 * Fires after a schedule is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $schedule_id Schedule ID.
		 * @param array $sanitized   Sanitized schedule data.
		 */
		do_action( 'flex_cs_schedule_updated', $schedule_id, $sanitized );
		$this->maybe_schedule_processing_event( (string) $sanitized['expiry_date'] );
		$old_post_id = isset( $existing->post_id ) ? (int) $existing->post_id : 0;
		$new_post_id = (int) $sanitized['post_id'];

		$this->invalidate_schedule_cache( $schedule_id, $old_post_id );
		if ( $old_post_id !== $new_post_id ) {
			$this->invalidate_schedule_cache( $schedule_id, $new_post_id );
		}
		$this->bump_count_cache_buster();

		return true;
	}

	/**
	 * Delete a schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param int $schedule_id Schedule ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_schedule( int $schedule_id ): bool {
		global $wpdb;

		$this->last_error = null;
		$this->ensure_table_ready();

		$suppress_state = $wpdb->suppress_errors( true );
		$result         = $wpdb->delete( ScheduleTable::get_table_name(), array( 'id' => $schedule_id ), array( '%d' ) );
		$wpdb->suppress_errors( $suppress_state );

		if ( false === $result ) {
			$this->last_error = new WP_Error(
				'flex_cs_db_delete_failed',
				! empty( $wpdb->last_error ) ? $wpdb->last_error : __( 'Database delete failed.', 'flex-content-scheduler' )
			);
			return false;
		}

		/**
		 * Fires after a schedule is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $schedule_id Schedule ID.
		 */
		do_action( 'flex_cs_schedule_deleted', $schedule_id );
		$this->invalidate_schedule_cache( $schedule_id, 0 );
		$this->bump_count_cache_buster();
		return true;
	}

	/**
	 * Retrieve a single schedule by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $schedule_id Schedule ID.
	 * @return object|null Schedule row object, or null if not found.
	 */
	public function get_schedule( int $schedule_id ) {
		global $wpdb;

		$cache_key = 'flex_cs_schedule_' . $schedule_id;
		$cached    = wp_cache_get( $cache_key, 'flex_cs' );
		if ( false !== $cached ) {
			return $cached;
		}

		$this->ensure_table_ready();
		$sql    = $wpdb->prepare( 'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE id = %d', $schedule_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		wp_cache_set( $cache_key, $result, 'flex_cs' );

		return $result;
	}

	/**
	 * Retrieve the active (unprocessed) schedule for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return object|null Schedule row object, or null if not found.
	 */
	public function get_schedule_by_post( int $post_id ) {
		global $wpdb;

		$cache_key = 'flex_cs_post_schedule_' . $post_id;
		$cached    = wp_cache_get( $cache_key, 'flex_cs' );
		if ( false !== $cached ) {
			return $cached;
		}

		$this->ensure_table_ready();
		$sql = $wpdb->prepare(
			'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE post_id = %d AND is_processed = 0 ORDER BY id DESC LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		);

		$result = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_set( $cache_key, $result, 'flex_cs' );

		return $result;
	}

	/**
	 * Retrieve all due (expired, unprocessed) schedules.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Maximum number of schedules to return. 0 uses the filtered default (50).
	 * @return array Array of schedule row objects.
	 */
	public function get_due_schedules( int $limit = 0 ): array {
		global $wpdb;

		$this->ensure_table_ready();

		/**
		 * Filter the maximum number of due schedules to process per batch.
		 *
		 * @since 1.0.0
		 *
		 * @param int $limit Default batch limit.
		 */
		$batch_limit = $limit > 0 ? $limit : (int) apply_filters( 'flex_cs_due_schedules_limit', 50 );

		$sql = $wpdb->prepare(
			'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE expiry_date <= UTC_TIMESTAMP() AND is_processed = 0 ORDER BY expiry_date ASC LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$batch_limit
		);
		$items = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $items ) ? $items : array();
	}

	/**
	 * Mark a schedule as processed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $schedule_id Schedule ID.
	 * @return bool True on success, false on failure.
	 */
	public function mark_processed( int $schedule_id ): bool {
		global $wpdb;

		$this->ensure_table_ready();
		$result = $wpdb->update(
			ScheduleTable::get_table_name(),
			array(
				'is_processed' => 1,
				'updated_at'   => current_time( 'mysql', true ),
			),
			array( 'id' => $schedule_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$this->invalidate_schedule_cache( $schedule_id, 0 );
			$this->bump_count_cache_buster();
		}

		return false !== $result;
	}

	/**
	 * Retrieve a paginated list of schedules with optional filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments (per_page, page, post_type, status).
	 * @return array Array of schedule row objects.
	 */
	public function get_all_schedules( array $args = array() ): array {
		global $wpdb;
		$this->ensure_table_ready();

		$defaults = array(
			'per_page'  => 20,
			'page'      => 1,
			'post_type' => '',
			'status'    => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$where  = array( '1=1' );

		if ( ! empty( $args['post_type'] ) ) {
			$where[] = $wpdb->prepare( 'p.post_type = %s', sanitize_key( $args['post_type'] ) );
		}

		if ( '' !== $args['status'] ) {
			$where[] = $wpdb->prepare( 's.is_processed = %d', (int) $args['status'] );
		}

		$sql = 'SELECT s.*, p.post_title, p.post_type, p.post_status
            FROM ' . ScheduleTable::get_table_name() . ' s
            LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = s.post_id
            WHERE ' . implode( ' AND ', $where ) .
			$wpdb->prepare( ' ORDER BY s.expiry_date ASC LIMIT %d OFFSET %d', (int) $args['per_page'], (int) $offset );

		$items = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Count schedules matching the given filters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments (post_type, status).
	 * @return int Total matching schedules.
	 */
	public function count_schedules( array $args = array() ): int {
		global $wpdb;
		$this->ensure_table_ready();

		$defaults = array(
			'post_type' => '',
			'status'    => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );

		if ( ! empty( $args['post_type'] ) ) {
			$where[] = $wpdb->prepare( 'p.post_type = %s', sanitize_key( $args['post_type'] ) );
		}

		if ( '' !== $args['status'] ) {
			$where[] = $wpdb->prepare( 's.is_processed = %d', (int) $args['status'] );
		}

		$cache_buster = (int) get_option( 'flex_cs_count_cache_buster', 1 );
		$cache_key    = 'flex_cs_count_' . md5( wp_json_encode( array( $args, $cache_buster ) ) );
		$cached       = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$sql = 'SELECT COUNT(*)
            FROM ' . ScheduleTable::get_table_name() . ' s
            LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = s.post_id
            WHERE ' . implode( ' AND ', $where );

		$result = (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		set_transient( $cache_key, $result, MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Validate schedule data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Schedule data to validate.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate( array $data ) {
		$required = array( 'post_id', 'expiry_date', 'expiry_action' );

		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				/* translators: %s: required schedule field key. */
				return new WP_Error( 'flex_cs_validation_error', sprintf( __( '%s is required.', 'flex-content-scheduler' ), $field ) );
			}
		}

		if ( ! get_post( (int) $data['post_id'] ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Invalid post ID.', 'flex-content-scheduler' ) );
		}

		$dt = \DateTime::createFromFormat( 'Y-m-d H:i:s', (string) $data['expiry_date'], new \DateTimeZone( 'UTC' ) );
		if ( ! $dt || $dt->format( 'Y-m-d H:i:s' ) !== $data['expiry_date'] ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Invalid expiry date format.', 'flex-content-scheduler' ) );
		}

		/**
		 * Filter the allowed expiry action types.
		 *
		 * Security-sensitive: third-party plugins can add custom action types.
		 * All returned values must be strings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $allowed_actions Array of action type strings.
		 */
			$allowed_actions = apply_filters( 'flex_cs_expiry_actions', array( 'unpublish', 'delete', 'redirect', 'change_status', 'sticky', 'unsticky' ) );

			// Validate filter output: ensure it is an array of strings.
		if ( ! is_array( $allowed_actions ) ) {
				$allowed_actions = array( 'unpublish', 'delete', 'redirect', 'change_status', 'sticky', 'unsticky' );
		} else {
			$allowed_actions = array_values( array_filter( $allowed_actions, 'is_string' ) );
		}

		if ( ! in_array( $data['expiry_action'], $allowed_actions, true ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Invalid expiry action.', 'flex-content-scheduler' ) );
		}

		if ( 'redirect' === $data['expiry_action'] && empty( $data['redirect_url'] ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Redirect URL is required.', 'flex-content-scheduler' ) );
		}

		if ( 'redirect' === $data['expiry_action'] && ! empty( $data['redirect_url'] ) ) {
			$redirect_host = wp_parse_url( (string) $data['redirect_url'], PHP_URL_HOST );
			if ( ! is_string( $redirect_host ) || '' === $redirect_host ) {
				return new WP_Error( 'flex_cs_validation_error', __( 'Invalid redirect URL host.', 'flex-content-scheduler' ) );
			}

			$allowed_hosts = $this->get_allowed_redirect_hosts();
			if ( ! empty( $allowed_hosts ) && ! in_array( strtolower( $redirect_host ), $allowed_hosts, true ) ) {
				return new WP_Error( 'flex_cs_validation_error', __( 'Redirect host is not allowed.', 'flex-content-scheduler' ) );
			}
		}

		if ( 'change_status' === $data['expiry_action'] && empty( $data['new_status'] ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'New status is required.', 'flex-content-scheduler' ) );
		}

		return true;
	}

	/**
	 * Sanitize schedule data for database storage.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Raw schedule data.
	 * @return array Sanitized schedule data.
	 */
	private function sanitize( array $data ): array {
		return array(
			'post_id'       => absint( $data['post_id'] ?? 0 ),
			'expiry_date'   => sanitize_text_field( (string) ( $data['expiry_date'] ?? '' ) ),
			'expiry_action' => sanitize_key( (string) ( $data['expiry_action'] ?? 'unpublish' ) ),
			'redirect_url'  => isset( $data['redirect_url'] ) ? esc_url_raw( (string) $data['redirect_url'] ) : null,
			'new_status'    => isset( $data['new_status'] ) ? sanitize_key( (string) $data['new_status'] ) : null,
			'is_processed'  => isset( $data['is_processed'] ) ? absint( $data['is_processed'] ) : 0,
		);
	}

	/**
	 * Get the last error encountered during a CRUD operation.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|null Last error, or null if no error.
	 */
	public function get_last_error(): ?WP_Error {
		return $this->last_error;
	}

	/**
	 * Ensure the schedules table exists, using a transient cache to avoid repeated checks.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_create Force table re-creation.
	 * @return void
	 */
	private function ensure_table_ready( bool $force_create = false ): void {
		global $wpdb;

		static $table_checked = false;
		if ( $table_checked && ! $force_create ) {
			return;
		}

		if ( ! defined( 'ABSPATH' ) ) {
			$table_checked = true;
			return;
		}

		$table_name       = ScheduleTable::get_table_name();
		$transient_key    = 'flex_cs_table_exists_' . md5( $table_name );
		$table_exists     = get_transient( $transient_key );

			// If transient exists and not forcing, skip the check.
		if ( ! $force_create && false !== $table_exists ) {
			$table_checked = true;
			return;
		}

			// Verify table exists in database.
			$suppress_state = $wpdb->suppress_errors( true );
			$exists         = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			$wpdb->suppress_errors( $suppress_state );

		if ( $force_create || $exists !== $table_name ) {
			$table = new ScheduleTable();
			$table->create_table();
		}

			// Cache the result for 1 hour.
			set_transient( $transient_key, '1', HOUR_IN_SECONDS );
			$table_checked = true;
	}

	/**
	 * Schedule a one-time cron event to process at the expiry time.
	 *
	 * @since 1.0.0
	 *
	 * @param string $utc_expiry_date UTC expiry datetime string.
	 * @return void
	 */
	private function maybe_schedule_processing_event( string $utc_expiry_date ): void {
		$settings = get_option( 'flex_cs_settings', array() );
		if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
			return;
		}

		$timestamp = strtotime( $utc_expiry_date . ' UTC' );
		if ( false === $timestamp ) {
			$timestamp = time() + 60;
		}

		if ( $timestamp <= time() ) {
			$timestamp = time() + 5;
		}

		wp_schedule_single_event( $timestamp, 'flex_cs_process_schedules' );
	}

	/**
	 * Invalidate cached schedule lookups.
	 *
	 * @since 1.0.0
	 *
	 * @param int $schedule_id Schedule ID.
	 * @param int $post_id     Post ID.
	 * @return void
	 */
	private function invalidate_schedule_cache( int $schedule_id, int $post_id ): void {
		if ( $schedule_id > 0 ) {
			wp_cache_delete( 'flex_cs_schedule_' . $schedule_id, 'flex_cs' );
		}

		if ( $post_id > 0 ) {
			wp_cache_delete( 'flex_cs_post_schedule_' . $post_id, 'flex_cs' );
		}
	}

	/**
	 * Remove any existing pending schedules for the post before creating/updating one.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id            Post ID.
	 * @param int $exclude_schedule_id Optional schedule ID to keep.
	 * @return void
	 */
	private function remove_existing_pending_schedules( int $post_id, int $exclude_schedule_id = 0 ): void {
		global $wpdb;

		if ( $post_id <= 0 ) {
			return;
		}

		if ( $exclude_schedule_id > 0 ) {
			$sql = $wpdb->prepare(
				'DELETE FROM ' . ScheduleTable::get_table_name() . ' WHERE post_id = %d AND is_processed = 0 AND id != %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_id,
				$exclude_schedule_id
			);
		} else {
			$sql = $wpdb->prepare(
				'DELETE FROM ' . ScheduleTable::get_table_name() . ' WHERE post_id = %d AND is_processed = 0', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$post_id
			);
		}

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Increment cache buster for count query transient keys.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function bump_count_cache_buster(): void {
		$current = (int) get_option( 'flex_cs_count_cache_buster', 1 );
		update_option( 'flex_cs_count_cache_buster', $current + 1, false );
	}

	/**
	 * Get configured and filtered allowed redirect hosts.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Sanitized lowercase hostnames.
	 */
	private function get_allowed_redirect_hosts(): array {
		$settings = get_option( 'flex_cs_settings', array() );
		$hosts    = $settings['allowed_redirect_hosts'] ?? array();

		if ( ! is_array( $hosts ) ) {
			$hosts = array();
		}

		$hosts = array_map(
			static function ( $host ): string {
				$sanitized = trim( strtolower( sanitize_text_field( (string) $host ) ) );
				return (string) preg_replace( '/[^a-z0-9\\.-]/', '', $sanitized );
			},
			$hosts
		);
		$hosts = array_values( array_filter( array_unique( $hosts ) ) );

		/**
		 * Filter redirect hosts allowed at schedule-save time.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $hosts Allowed hostnames.
		 */
		$hosts = (array) apply_filters( 'flex_cs_allowed_redirect_hosts', $hosts );
		$hosts = array_map( 'strtolower', array_values( array_filter( $hosts, 'is_string' ) ) );

		return array_values( array_unique( $hosts ) );
	}
}
