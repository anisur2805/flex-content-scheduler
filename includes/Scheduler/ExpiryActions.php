<?php
/**
 * Expiry actions handler.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Scheduler;

/**
 * Class ExpiryActions
 *
 * Executes expiry actions for scheduled posts.
 *
 * @since 1.0.0
 */
class ExpiryActions {
	/**
	 * Host temporarily allowed for safe redirect.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $current_redirect_host = '';

	/**
	 * Process a single schedule action.
	 *
	 * @since 1.0.0
	 *
	 * @param object $schedule Schedule row object.
	 * @return bool True on success, false on failure.
	 */
	public function process( object $schedule ): bool {
		$post_id = isset( $schedule->post_id ) ? (int) $schedule->post_id : 0;

		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return false;
		}

		/**
		 * Fires before processing an expiry action.
		 *
		 * @since 1.0.0
		 *
		 * @param object $schedule Schedule row object.
		 */
		do_action( 'flex_cs_before_expiry_action', $schedule );

		$result = false;

		switch ( $schedule->expiry_action ) {
			case 'unpublish':
				$result = $this->unpublish( $post_id );
				break;
			case 'delete':
				$result = $this->delete_post( $post_id );
				break;
			case 'redirect':
				$result = $this->redirect( $post_id, (string) ( $schedule->redirect_url ?? '' ) );
				break;
			case 'change_status':
				$result = $this->change_status( $post_id, (string) ( $schedule->new_status ?? 'draft' ) );
				break;
		}

		/**
		 * Fires after processing an expiry action.
		 *
		 * @since 1.0.0
		 *
		 * @param object $schedule Schedule row object.
		 * @param bool   $result   Whether the action succeeded.
		 */
		do_action( 'flex_cs_after_expiry_action', $schedule, $result );

		return $result;
	}

	/**
	 * Unpublish a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	public function unpublish( int $post_id ): bool {
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			),
			true
		);

		// If a previous redirect action was processed, clear it for non-redirect actions.
		delete_post_meta( $post_id, '_flex_cs_redirect_url' );

		return ! is_wp_error( $result );
	}

	/**
	 * Permanently delete a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_post( int $post_id ): bool {
		return (bool) wp_delete_post( $post_id, true );
	}

	/**
	 * Save redirect destination for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Redirect URL.
	 * @return bool True on success, false on failure.
	 */
	public function redirect( int $post_id, string $url ): bool {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$redirect_url = esc_url_raw( $url );
		$current_url  = (string) get_post_meta( $post_id, '_flex_cs_redirect_url', true );

		if ( $current_url === $redirect_url ) {
			return true;
		}

		$updated = update_post_meta( $post_id, '_flex_cs_redirect_url', $redirect_url );
		if ( false === $updated ) {
			return false;
		}

		// Keep post publicly reachable so template_redirect can execute and forward visitors.
		$publish_result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		return ! is_wp_error( $publish_result );
	}

	/**
	 * Change post status.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $new_status New status.
	 * @return bool True on success, false on failure.
	 */
	public function change_status( int $post_id, string $new_status ): bool {
		if ( empty( $new_status ) ) {
			return false;
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => sanitize_key( $new_status ),
			),
			true
		);

		// If a previous redirect action was processed, clear it for non-redirect actions.
		delete_post_meta( $post_id, '_flex_cs_redirect_url' );

		return ! is_wp_error( $result );
	}

	/**
	 * Redirect single post requests when redirect URL is set.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_template_redirect(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$url = get_post_meta( $post_id, '_flex_cs_redirect_url', true );
		if ( ! empty( $url ) && wp_http_validate_url( $url ) ) {
			$redirect_url = esc_url_raw( $url );
			$host         = wp_parse_url( $redirect_url, PHP_URL_HOST );

			if ( ! is_string( $host ) || '' === $host ) {
				return;
			}

			$host = strtolower( $host );

			$settings_hosts = $this->get_configured_redirect_hosts();

			/**
			 * Filter the allowed redirect hosts for content expiry redirects.
			 *
			 * Return an array of allowed domain strings to restrict which hosts
			 * can be used as redirect destinations. Return an empty array to
			 * allow any host (default behaviour).
			 *
			 * @since 1.0.0
			 *
			 * @param array<int, string> $settings_hosts Hostnames from plugin settings.
			 */
			$allowed_hosts = (array) apply_filters( 'flex_cs_allowed_redirect_hosts', $settings_hosts );

			if ( ! empty( $allowed_hosts ) && ! in_array( $host, array_map( 'strtolower', $allowed_hosts ), true ) ) {
				return;
			}

			$this->current_redirect_host = $host;
			add_filter( 'allowed_redirect_hosts', array( $this, 'allow_current_redirect_host' ) );

			$this->perform_redirect( $redirect_url );
			remove_filter( 'allowed_redirect_hosts', array( $this, 'allow_current_redirect_host' ) );
		}
	}

	/**
	 * Allow current redirect host for safe redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $hosts Allowed hosts.
	 * @return array<int, string> Modified hosts array.
	 */
	public function allow_current_redirect_host( array $hosts ): array {
		if ( '' !== $this->current_redirect_host && ! in_array( $this->current_redirect_host, $hosts, true ) ) {
			$hosts[] = $this->current_redirect_host;
		}

		return $hosts;
	}

	/**
	 * Get configured redirect allowlist hosts from plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Sanitized lowercase host list.
	 */
	private function get_configured_redirect_hosts(): array {
		$settings = get_option( 'flex_cs_settings', array() );
		$hosts    = $settings['allowed_redirect_hosts'] ?? array();

		if ( ! is_array( $hosts ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $hosts as $host ) {
			$host = trim( strtolower( sanitize_text_field( (string) $host ) ) );
			$host = preg_replace( '/[^a-z0-9\\.-]/', '', $host );
			if ( '' !== $host ) {
				$sanitized[] = $host;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Perform the final redirect response.
	 *
	 * Separated for unit-test override so redirect behavior can be asserted
	 * without terminating the PHP process.
	 *
	 * @since 1.0.0
	 *
	 * @param string $redirect_url Redirect destination URL.
	 * @return void
	 */
	protected function perform_redirect( string $redirect_url ): void {
		wp_safe_redirect( $redirect_url, 301, 'Flex Content Scheduler' );
		exit;
	}
}
