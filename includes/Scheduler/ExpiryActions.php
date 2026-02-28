<?php
/**
 * Expiry actions handler.
 *
 * @package FlexContentScheduler
 */

namespace Anisur\ContentScheduler\Scheduler;

/**
 * Executes expiry actions for scheduled posts.
 */
class ExpiryActions {
	/**
	 * Host temporarily allowed for safe redirect.
	 *
	 * @var string
	 */
	private string $current_redirect_host = '';

	/**
	 * Process a single schedule action.
	 *
	 * @param object $schedule Schedule row object.
	 * @return bool
	 */
	public function process( object $schedule ): bool {
		$post_id = isset( $schedule->post_id ) ? (int) $schedule->post_id : 0;

		if ( $post_id <= 0 || ! get_post( $post_id ) ) {
			return false;
		}

		do_action( 'fcs_before_expiry_action', $schedule );

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

		do_action( 'fcs_after_expiry_action', $schedule, $result );

		return $result;
	}

	/**
	 * Unpublish a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
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
		delete_post_meta( $post_id, '_fcs_redirect_url' );

		return ! is_wp_error( $result );
	}

	/**
	 * Permanently delete a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function delete_post( int $post_id ): bool {
		return (bool) wp_delete_post( $post_id, true );
	}

	/**
	 * Save redirect destination for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url Redirect URL.
	 * @return bool
	 */
	public function redirect( int $post_id, string $url ): bool {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return false;
		}

		return (bool) update_post_meta( $post_id, '_fcs_redirect_url', esc_url_raw( $url ) );
	}

	/**
	 * Change post status.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $new_status New status.
	 * @return bool
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
		delete_post_meta( $post_id, '_fcs_redirect_url' );

		return ! is_wp_error( $result );
	}

	/**
	 * Redirect single post requests when redirect URL is set.
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

		$url = get_post_meta( $post_id, '_fcs_redirect_url', true );
		if ( ! empty( $url ) && wp_http_validate_url( $url ) ) {
			$redirect_url = esc_url_raw( $url );
			$host         = wp_parse_url( $redirect_url, PHP_URL_HOST );

			if ( is_string( $host ) && '' !== $host ) {
				$this->current_redirect_host = strtolower( $host );
				add_filter( 'allowed_redirect_hosts', array( $this, 'allow_current_redirect_host' ) );
			}

			wp_safe_redirect( $redirect_url, 301, 'Flex Content Scheduler' );
			remove_filter( 'allowed_redirect_hosts', array( $this, 'allow_current_redirect_host' ) );
			exit;
		}
	}

	/**
	 * Allow current redirect host for safe redirects.
	 *
	 * @param array<int, string> $hosts Allowed hosts.
	 * @return array<int, string>
	 */
	public function allow_current_redirect_host( array $hosts ): array {
		if ( '' !== $this->current_redirect_host && ! in_array( $this->current_redirect_host, $hosts, true ) ) {
			$hosts[] = $this->current_redirect_host;
		}

		return $hosts;
	}
}
