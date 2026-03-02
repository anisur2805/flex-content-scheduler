<?php
/**
 * Automator action: Redirect a post.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions;

use Anisur\ContentScheduler\Scheduler\ExpiryActions;

/**
 * Class RedirectPostAction
 *
 * Handles the Automator action to redirect a post via its expiry URL.
 *
 * @since 1.0.0
 */
class RedirectPostAction {
	/**
	 * Expiry actions handler.
	 *
	 * @since 1.0.0
	 * @var ExpiryActions
	 */
	private ExpiryActions $expiry_actions;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ExpiryActions $expiry_actions Expiry actions handler.
	 */
	public function __construct( ExpiryActions $expiry_actions ) {
		$this->expiry_actions = $expiry_actions;
		add_action( 'flex_cs_automator_action_redirect', array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Handle the redirect action.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     Redirect URL.
	 * @return void
	 */
	public function handle( int $post_id, string $url ): void {
		$this->expiry_actions->redirect( $post_id, $url );
	}
}
