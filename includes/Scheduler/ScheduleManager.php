<?php

namespace Anisur\ContentScheduler\Scheduler;

use Anisur\ContentScheduler\Database\ScheduleTable;
use WP_Error;

class ScheduleManager {
	private ?WP_Error $last_error = null;

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
		do_action( 'flex_cs_schedule_created', $id, $sanitized );
		$this->maybe_schedule_processing_event( (string) $sanitized['expiry_date'] );

		return $id;
	}

	public function update_schedule( int $schedule_id, array $data ): bool {
		global $wpdb;

		$this->last_error = null;
		$this->ensure_table_ready();

		if ( ! $this->get_schedule( $schedule_id ) ) {
			$this->last_error = new WP_Error( 'flex_cs_schedule_not_found', __( 'Schedule not found.', 'flex-content-scheduler' ) );
			return false;
		}

		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			$this->last_error = $validation;
			return false;
		}

		$sanitized = $this->sanitize( $data );

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

		do_action( 'flex_cs_schedule_updated', $schedule_id, $sanitized );
		$this->maybe_schedule_processing_event( (string) $sanitized['expiry_date'] );

		return true;
	}

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

		do_action( 'flex_cs_schedule_deleted', $schedule_id );
		return true;
	}

	public function get_schedule( int $schedule_id ) {
		global $wpdb;

		$this->ensure_table_ready();
		$sql = $wpdb->prepare( 'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE id = %d', $schedule_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $sql );
	}

	public function get_schedule_by_post( int $post_id ) {
		global $wpdb;

		$this->ensure_table_ready();
		$sql = $wpdb->prepare(
			'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE post_id = %d AND is_processed = 0 ORDER BY id DESC LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		);

		return $wpdb->get_row( $sql );
	}

	public function get_due_schedules(): array {
		global $wpdb;

		$this->ensure_table_ready();
		$sql = 'SELECT * FROM ' . ScheduleTable::get_table_name() . ' WHERE expiry_date <= UTC_TIMESTAMP() AND is_processed = 0'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $sql );

		return is_array( $items ) ? $items : array();
	}

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

		return false !== $result;
	}

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

		$sql = 'SELECT COUNT(*)
            FROM ' . ScheduleTable::get_table_name() . ' s
            LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = s.post_id
            WHERE ' . implode( ' AND ', $where );

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function validate( array $data ) {
		$required = array( 'post_id', 'expiry_date', 'expiry_action' );

		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
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

		$allowed_actions = apply_filters( 'flex_cs_expiry_actions', array( 'unpublish', 'delete', 'redirect', 'change_status' ) );

		if ( ! in_array( $data['expiry_action'], $allowed_actions, true ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Invalid expiry action.', 'flex-content-scheduler' ) );
		}

		if ( 'redirect' === $data['expiry_action'] && empty( $data['redirect_url'] ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'Redirect URL is required.', 'flex-content-scheduler' ) );
		}

		if ( 'change_status' === $data['expiry_action'] && empty( $data['new_status'] ) ) {
			return new WP_Error( 'flex_cs_validation_error', __( 'New status is required.', 'flex-content-scheduler' ) );
		}

		return true;
	}

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

	public function get_last_error(): ?WP_Error {
		return $this->last_error;
	}

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

		$table_name     = ScheduleTable::get_table_name();
		$suppress_state = $wpdb->suppress_errors( true );
		$exists         = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		$wpdb->suppress_errors( $suppress_state );

		if ( $force_create || $exists !== $table_name ) {
			$table = new ScheduleTable();
			$table->create_table();
		}

		$table_checked = true;
	}

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
}
