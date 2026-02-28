<?php

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions;

use Anisur\ContentScheduler\Scheduler\ExpiryActions;

class RedirectPostAction {
	private ExpiryActions $expiry_actions;

	public function __construct( ExpiryActions $expiry_actions ) {
		$this->expiry_actions = $expiry_actions;
		add_action( 'fcs_automator_action_redirect', array( $this, 'handle' ), 10, 2 );
	}

	public function handle( int $post_id, string $url ): void {
		$this->expiry_actions->redirect( $post_id, $url );
	}
}
