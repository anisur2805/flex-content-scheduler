<?php

namespace Anisur\ContentScheduler\Api;

use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

class ScheduleRestController extends WP_REST_Controller {
	protected $namespace = 'fcs/v1';
	protected $rest_base = 'schedules';

	private ScheduleManager $schedule_manager;

	public function __construct( ScheduleManager $schedule_manager ) {
		$this->schedule_manager = $schedule_manager;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'per_page'  => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'page'      => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'post_type' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'status'    => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/post/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item_by_post' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'manage_settings_permissions_check' ),
				),
				array(
					'methods'             => 'PUT,PATCH',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'manage_settings_permissions_check' ),
				),
			)
		);
	}

	public function get_items( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$per_page = (int) $request->get_param( 'per_page' ) ?: 20;
			$page     = (int) $request->get_param( 'page' ) ?: 1;
			$filters  = array(
				'per_page'  => $per_page,
				'page'      => $page,
				'post_type' => sanitize_key( (string) $request->get_param( 'post_type' ) ),
				'status'    => '' === $request->get_param( 'status' ) || null === $request->get_param( 'status' ) ? '' : (int) $request->get_param( 'status' ),
			);

			$items = $this->schedule_manager->get_all_schedules( $filters );
			$total = $this->schedule_manager->count_schedules( $filters );

			$response = rest_ensure_response( $items );
			$response->header( 'X-WP-Total', (string) $total );

			return $response;
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function create_item( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$data = $this->normalize_schedule_request_data( $request );
			$id   = $this->schedule_manager->create_schedule( $data );

			if ( false === $id ) {
				$error = $this->schedule_manager->get_last_error();
				if ( $error instanceof WP_Error ) {
					return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
				}

				return new WP_Error( 'fcs_create_failed', __( 'Could not create schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
			}

			$item = $this->schedule_manager->get_schedule( (int) $id );

			return new WP_REST_Response( $this->prepare_schedule_data( $item ), 201 );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function get_item( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$id   = (int) $request->get_param( 'id' );
			$item = $this->schedule_manager->get_schedule( $id );

			if ( ! $item ) {
				return new WP_Error( 'fcs_not_found', __( 'Schedule not found.', 'flex-content-scheduler' ), array( 'status' => 404 ) );
			}

			return $this->prepare_item_for_response( $item, $request );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function update_item( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$id   = (int) $request->get_param( 'id' );
			$data = $this->normalize_schedule_request_data( $request );

			$ok = $this->schedule_manager->update_schedule( $id, $data );
			if ( ! $ok ) {
				$error = $this->schedule_manager->get_last_error();
				if ( $error instanceof WP_Error ) {
					return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
				}
				return new WP_Error( 'fcs_update_failed', __( 'Could not update schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
			}

			return new WP_REST_Response( $this->prepare_schedule_data( $this->schedule_manager->get_schedule( $id ) ), 200 );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function delete_item( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$id = (int) $request->get_param( 'id' );
			if ( ! $this->schedule_manager->delete_schedule( $id ) ) {
				$error = $this->schedule_manager->get_last_error();
				if ( $error instanceof WP_Error ) {
					return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
				}
				return new WP_Error( 'fcs_delete_failed', __( 'Could not delete schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
			}

			return new WP_REST_Response( array( 'deleted' => true ), 200 );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function get_item_by_post( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$post_id = (int) $request->get_param( 'post_id' );
			$item    = $this->schedule_manager->get_schedule_by_post( $post_id );

			if ( ! $item ) {
				return new WP_REST_Response( (object) array(), 200 );
			}

			return $this->prepare_item_for_response( $item, $request );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function get_item_permissions_check( $request ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'You are not allowed to access schedules.', 'flex-content-scheduler' ), array( 'status' => 401 ) );
	}

	public function create_item_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	public function manage_settings_permissions_check( $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'You are not allowed to manage settings.', 'flex-content-scheduler' ), array( 'status' => 401 ) );
	}

	public function get_settings( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			return new WP_REST_Response( $this->get_sanitized_settings(), 200 );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function update_settings( $request ) {
		$buffer_level = $this->begin_request_guard();
		try {
			$incoming = $request->get_json_params();
			$incoming = is_array( $incoming ) ? $incoming : array();

			$settings = array(
				'default_action'     => sanitize_key( (string) ( $incoming['default_action'] ?? 'unpublish' ) ),
				'cron_enabled'       => ! empty( $incoming['cron_enabled'] ),
				'notification_email' => sanitize_email( (string) ( $incoming['notification_email'] ?? '' ) ),
			);

			$allowed_actions = array( 'unpublish', 'delete', 'redirect', 'change_status' );
			if ( ! in_array( $settings['default_action'], $allowed_actions, true ) ) {
				return new WP_Error( 'fcs_invalid_default_action', __( 'Invalid default action.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
			}

			update_option( 'fcs_settings', $settings, false );

			return new WP_REST_Response( $this->get_sanitized_settings(), 200 );
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'fcs_schedule',
			'type'       => 'object',
			'properties' => array(
				'id'            => array( 'type' => 'integer' ),
				'post_id'       => array( 'type' => 'integer' ),
				'expiry_date'   => array( 'type' => 'string' ),
				'expiry_action' => array( 'type' => 'string' ),
				'redirect_url'  => array( 'type' => array( 'string', 'null' ) ),
				'new_status'    => array( 'type' => array( 'string', 'null' ) ),
				'is_processed'  => array( 'type' => 'integer' ),
				'created_at'    => array( 'type' => 'string' ),
				'updated_at'    => array( 'type' => 'string' ),
			),
		);
	}

	public function prepare_item_for_response( $schedule, $request ) {
		return new WP_REST_Response( $this->prepare_schedule_data( $schedule ), 200 );
	}

	private function normalize_schedule_request_data( WP_REST_Request $request ): array {
		$data = $request->get_json_params();
		$data = is_array( $data ) ? $data : array();

		return array(
			'post_id'       => absint( $data['post_id'] ?? 0 ),
			'expiry_date'   => sanitize_text_field( (string) ( $data['expiry_date'] ?? '' ) ),
			'expiry_action' => sanitize_key( (string) ( $data['expiry_action'] ?? 'unpublish' ) ),
			'redirect_url'  => isset( $data['redirect_url'] ) ? esc_url_raw( (string) $data['redirect_url'] ) : '',
			'new_status'    => isset( $data['new_status'] ) ? sanitize_key( (string) $data['new_status'] ) : '',
		);
	}

	private function prepare_schedule_data( $schedule ): ?array {
		if ( ! is_object( $schedule ) ) {
			return null;
		}

		return array(
			'id'            => (int) $schedule->id,
			'post_id'       => (int) $schedule->post_id,
			'expiry_date'   => (string) $schedule->expiry_date,
			'expiry_action' => (string) $schedule->expiry_action,
			'redirect_url'  => ! empty( $schedule->redirect_url ) ? esc_url_raw( (string) $schedule->redirect_url ) : null,
			'new_status'    => ! empty( $schedule->new_status ) ? sanitize_key( (string) $schedule->new_status ) : null,
			'is_processed'  => (int) $schedule->is_processed,
			'created_at'    => (string) $schedule->created_at,
			'updated_at'    => (string) $schedule->updated_at,
		);
	}

	private function get_sanitized_settings(): array {
		$settings = get_option( 'fcs_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'default_action'     => sanitize_key( (string) ( $settings['default_action'] ?? 'unpublish' ) ),
			'cron_enabled'       => ! isset( $settings['cron_enabled'] ) || (bool) $settings['cron_enabled'],
			'notification_email' => sanitize_email( (string) ( $settings['notification_email'] ?? '' ) ),
		);
	}

	private function begin_request_guard(): int {
		@ini_set( 'display_errors', '0' );
		$level = ob_get_level();
		ob_start();

		return $level;
	}

	private function end_request_guard( int $initial_level ): void {
		while ( ob_get_level() > $initial_level ) {
			$buffer = (string) ob_get_clean();
			if ( '' !== trim( $buffer ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[FCS] Unexpected REST output: ' . $buffer );
			}
		}
	}
}
