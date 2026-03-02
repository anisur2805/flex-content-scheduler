<?php
/**
 * REST API controller for schedules.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Api;

use Anisur\ContentScheduler\Helpers\Logger;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class ScheduleRestController
 *
 * Provides REST API endpoints for managing content expiry schedules.
 *
 * @since 1.0.0
 */
class ScheduleRestController extends WP_REST_Controller {
	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $namespace = 'flex-cs/v1';

	/**
	 * REST API base path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'schedules';

	/**
	 * Schedule manager instance.
	 *
	 * @since 1.0.0
	 * @var ScheduleManager
	 */
	private ScheduleManager $schedule_manager;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ScheduleManager $schedule_manager Schedule manager instance.
	 */
	public function __construct( ScheduleManager $schedule_manager ) {
		$this->schedule_manager = $schedule_manager;
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
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

	/**
	 * Retrieve a paginated list of schedules.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function get_items( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$per_page = (int) $request->get_param( 'per_page' );
				$page     = (int) $request->get_param( 'page' );
				if ( $per_page <= 0 ) {
					$per_page = 20;
				}

				if ( $page <= 0 ) {
					$page = 1;
				}

				$status_value = $request->get_param( 'status' );

				$filters = array(
					'per_page'  => $per_page,
					'page'      => $page,
					'post_type' => sanitize_key( (string) $request->get_param( 'post_type' ) ),
					'status'    => ( '' === $status_value || null === $status_value ) ? '' : (int) $status_value,
				);

				$items = $this->schedule_manager->get_all_schedules( $filters );
				$total = $this->schedule_manager->count_schedules( $filters );

				$response = rest_ensure_response( $items );
				$response->header( 'X-WP-Total', (string) $total );

				return $response;
			}
		);
	}

	/**
	 * Create a new schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function create_item( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$data = $this->normalize_schedule_request_data( $request );
				$id   = $this->schedule_manager->create_schedule( $data );

				if ( false === $id ) {
					$error = $this->schedule_manager->get_last_error();
					if ( $error instanceof WP_Error ) {
						return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
					}

					return new WP_Error( 'flex_cs_create_failed', __( 'Could not create schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
				}

				$item = $this->schedule_manager->get_schedule( (int) $id );

				return new WP_REST_Response( $this->prepare_schedule_data( $item ), 201 );
			}
		);
	}

	/**
	 * Retrieve a single schedule by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function get_item( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$id   = (int) $request->get_param( 'id' );
				$item = $this->schedule_manager->get_schedule( $id );

				if ( ! $item ) {
					return new WP_Error( 'flex_cs_not_found', __( 'Schedule not found.', 'flex-content-scheduler' ), array( 'status' => 404 ) );
				}

				return $this->prepare_item_for_response( $item, $request );
			}
		);
	}

	/**
	 * Update an existing schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function update_item( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$id   = (int) $request->get_param( 'id' );
				$data = $this->normalize_schedule_request_data( $request );

				$ok = $this->schedule_manager->update_schedule( $id, $data );
				if ( ! $ok ) {
					$error = $this->schedule_manager->get_last_error();
					if ( $error instanceof WP_Error ) {
						return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
					}
					return new WP_Error( 'flex_cs_update_failed', __( 'Could not update schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
				}

				return new WP_REST_Response( $this->prepare_schedule_data( $this->schedule_manager->get_schedule( $id ) ), 200 );
			}
		);
	}

	/**
	 * Delete a schedule.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function delete_item( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$id = (int) $request->get_param( 'id' );
				if ( ! $this->schedule_manager->delete_schedule( $id ) ) {
					$error = $this->schedule_manager->get_last_error();
					if ( $error instanceof WP_Error ) {
						return new WP_Error( $error->get_error_code(), $error->get_error_message(), array( 'status' => 400 ) );
					}
					return new WP_Error( 'flex_cs_delete_failed', __( 'Could not delete schedule.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
				}

				return new WP_REST_Response( array( 'deleted' => true ), 200 );
			}
		);
	}

	/**
	 * Retrieve the active schedule for a specific post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function get_item_by_post( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$post_id = (int) $request->get_param( 'post_id' );
				$item    = $this->schedule_manager->get_schedule_by_post( $post_id );

				if ( ! $item ) {
					return new WP_REST_Response( (object) array(), 200 );
				}

				return $this->prepare_item_for_response( $item, $request );
			}
		);
	}

	/**
	 * Check if the current user can read schedules.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'You are not allowed to access schedules.', 'flex-content-scheduler' ), array( 'status' => 401 ) );
	}

	/**
	 * Check if the current user can create, update, or delete a schedule.
	 *
	 * Validates that the user has `edit_post` capability for the specific post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$write_validation = $this->validate_write_request( $request );
		if ( is_wp_error( $write_validation ) ) {
			return $write_validation;
		}

		// For create/update/delete operations, check specific post permissions.
		$post_id = 0;

		// Get post_id from the request.
		if ( $request->get_param( 'id' ) ) {
			// Update or delete: get post_id from the schedule.
			$schedule_id = (int) $request->get_param( 'id' );
			$schedule    = $this->schedule_manager->get_schedule( $schedule_id );
			if ( $schedule ) {
				$post_id = (int) $schedule->post_id;
			}
		} else {
			// Create: get post_id from request body.
			$data = $request->get_json_params();
			$data = is_array( $data ) ? $data : array();
			$post_id = isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;
		}

		if ( ! $post_id ) {
			return new WP_Error( 'rest_forbidden', __( 'Invalid post ID.', 'flex-content-scheduler' ), array( 'status' => 403 ) );
		}

		// Check if user can edit this specific post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You are not allowed to manage schedules for this post.', 'flex-content-scheduler' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Check if the current user can manage plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if permitted, WP_Error otherwise.
	 */
	public function manage_settings_permissions_check( $request ) {
		if ( in_array( strtoupper( (string) $request->get_method() ), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			$write_validation = $this->validate_write_request( $request );
			if ( is_wp_error( $write_validation ) ) {
				return $write_validation;
			}
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', __( 'You are not allowed to manage settings.', 'flex-content-scheduler' ), array( 'status' => 401 ) );
	}

	/**
	 * Retrieve plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function get_settings( $request ) {
		return $this->run_guarded(
			function () {
				return new WP_REST_Response( $this->get_sanitized_settings(), 200 );
			}
		);
	}

	/**
	 * Update plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
	 */
	public function update_settings( $request ) {
		return $this->run_guarded(
			function () use ( $request ) {
				$incoming = $request->get_json_params();
				$incoming = is_array( $incoming ) ? $incoming : array();

				$settings = array(
					'default_action'         => sanitize_key( (string) ( $incoming['default_action'] ?? 'unpublish' ) ),
					'cron_enabled'           => ! empty( $incoming['cron_enabled'] ),
					'notification_email'     => sanitize_email( (string) ( $incoming['notification_email'] ?? '' ) ),
					'allowed_redirect_hosts' => $this->sanitize_redirect_hosts( $incoming['allowed_redirect_hosts'] ?? array() ),
				);

				$allowed_actions = array( 'unpublish', 'delete', 'redirect', 'change_status' );
				if ( ! in_array( $settings['default_action'], $allowed_actions, true ) ) {
					return new WP_Error( 'flex_cs_invalid_default_action', __( 'Invalid default action.', 'flex-content-scheduler' ), array( 'status' => 400 ) );
				}

				update_option( 'flex_cs_settings', $settings, false );

				return new WP_REST_Response( $this->get_sanitized_settings(), 200 );
			}
		);
	}

	/**
	 * Get the schedule schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'flex_cs_schedule',
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

	/**
	 * Prepare a schedule for the REST response.
	 *
	 * @since 1.0.0
	 *
	 * @param object          $schedule Schedule row object.
	 * @param WP_REST_Request $request  Full details about the request.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $schedule, $request ) {
		return new WP_REST_Response( $this->prepare_schedule_data( $schedule ), 200 );
	}

	/**
	 * Normalize and sanitize schedule data from a REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Sanitized schedule data.
	 */
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

	/**
	 * Prepare a schedule object as an associative array for response.
	 *
	 * @since 1.0.0
	 *
	 * @param object|null $schedule Schedule row object.
	 * @return array|null Prepared schedule data, or null if input is invalid.
	 */
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

	/**
	 * Retrieve and sanitize plugin settings from the database.
	 *
	 * @since 1.0.0
	 *
	 * @return array Sanitized settings array.
	 */
	private function get_sanitized_settings(): array {
		$settings = get_option( 'flex_cs_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'default_action'         => sanitize_key( (string) ( $settings['default_action'] ?? 'unpublish' ) ),
			'cron_enabled'           => ! isset( $settings['cron_enabled'] ) || (bool) $settings['cron_enabled'],
			'notification_email'     => sanitize_email( (string) ( $settings['notification_email'] ?? '' ) ),
			'allowed_redirect_hosts' => $this->sanitize_redirect_hosts( $settings['allowed_redirect_hosts'] ?? array() ),
		);
	}

	/**
	 * Sanitize a redirect-host allowlist value from settings input.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw host-list value.
	 * @return array<int, string> Sanitized unique lowercase hostnames.
	 */
	private function sanitize_redirect_hosts( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$hosts = array();
		foreach ( $value as $host ) {
			$host = trim( strtolower( sanitize_text_field( (string) $host ) ) );
			$host = preg_replace( '/[^a-z0-9\\.-]/', '', $host );
			if ( '' !== $host ) {
				$hosts[] = $host;
			}
		}

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Begin output buffering guard for REST responses.
	 *
	 * Captures any unexpected output (PHP notices/warnings) that could pollute JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return int The output buffer level before starting.
	 */
	private function begin_request_guard(): int {
		$level = ob_get_level();
		ob_start();

		return $level;
	}

	/**
	 * End output buffering guard and log any unexpected output.
	 *
	 * @since 1.0.0
	 *
	 * @param int $initial_level The output buffer level before the guard started.
	 * @return void
	 */
	private function end_request_guard( int $initial_level ): void {
		while ( ob_get_level() > $initial_level ) {
			$buffer = (string) ob_get_clean();
			if ( '' !== trim( $buffer ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				Logger::debug( 'Unexpected REST output: ' . $buffer );
			}
		}
	}

	/**
	 * Run a callback inside the request output guard.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $callback Callback to execute.
	 * @return mixed Callback result.
	 */
	private function run_guarded( callable $callback ) {
		$buffer_level = $this->begin_request_guard();
		try {
			return $callback();
		} finally {
			$this->end_request_guard( $buffer_level );
		}
	}

	/**
	 * Validate nonce and rate limits for write requests.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True when valid, WP_Error on failure.
	 */
	private function validate_write_request( WP_REST_Request $request ) {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Invalid or missing REST nonce.', 'flex-content-scheduler' ),
				array( 'status' => 403 )
			);
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Authentication required.', 'flex-content-scheduler' ),
				array( 'status' => 401 )
			);
		}

		return $this->enforce_write_rate_limit( $user_id, $request );
	}

	/**
	 * Enforce a per-user write rate limit for REST endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @param int             $user_id User ID.
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True when allowed, WP_Error when rate limited.
	 */
	private function enforce_write_rate_limit( int $user_id, WP_REST_Request $request ) {
		$limit = (int) apply_filters( 'flex_cs_rest_write_rate_limit', 60, $request );
		if ( $limit <= 0 ) {
			return true;
		}

		$window_seconds = (int) apply_filters( 'flex_cs_rest_write_rate_window', MINUTE_IN_SECONDS, $request );
		if ( $window_seconds <= 0 ) {
			$window_seconds = MINUTE_IN_SECONDS;
		}

		$key   = 'flex_cs_rl_' . $user_id;
		$state = get_transient( $key );
		$now   = time();

		if ( ! is_array( $state ) || ! isset( $state['started_at'], $state['count'] ) ) {
			$state = array(
				'started_at' => $now,
				'count'      => 0,
			);
		}

		if ( ( $now - (int) $state['started_at'] ) >= $window_seconds ) {
			$state = array(
				'started_at' => $now,
				'count'      => 0,
			);
		}

		if ( (int) $state['count'] >= $limit ) {
			return new WP_Error(
				'flex_cs_rate_limited',
				__( 'Too many schedule write requests. Please wait and try again.', 'flex-content-scheduler' ),
				array( 'status' => 429 )
			);
		}

		$state['count'] = (int) $state['count'] + 1;
		set_transient( $key, $state, $window_seconds );

		return true;
	}
}
