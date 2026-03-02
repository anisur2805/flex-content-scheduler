<?php
/**
 * Automator action: Unpublish a post.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions;

use Anisur\ContentScheduler\Scheduler\ExpiryActions;

/**
 * Class UnpublishPostAction
 *
 * Handles the Automator action to unpublish (draft) a post.
 *
 * @since 1.0.0
 */
class UnpublishPostAction {
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
		add_action( 'flex_cs_automator_action_unpublish', array( $this, 'handle' ), 10, 1 );
	}

	/**
	 * Handle the unpublish action.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function handle( int $post_id ): void {
		$this->expiry_actions->unpublish( $post_id );
	}
}
