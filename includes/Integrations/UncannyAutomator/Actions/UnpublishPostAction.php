<?php

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions;

use Anisur\ContentScheduler\Scheduler\ExpiryActions;

class UnpublishPostAction {
	private ExpiryActions $expiry_actions;

	public function __construct( ExpiryActions $expiry_actions ) {
		$this->expiry_actions = $expiry_actions;
		add_action( 'fcs_automator_action_unpublish', array( $this, 'handle' ), 10, 1 );
	}

	public function handle( int $post_id ): void {
		$this->expiry_actions->unpublish( $post_id );
	}
}
