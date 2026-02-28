<?php

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator;

use Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions\RedirectPostAction;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions\UnpublishPostAction;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers\ContentExpiredTrigger;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers\ContentScheduledTrigger;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;

class AutomatorIntegration {
	private ?ExpiryActions $expiry_actions = null;

	public function __construct( ExpiryActions $expiry_actions ) {
		if ( ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			return;
		}

		$this->expiry_actions = $expiry_actions;
	}

	public function register(): void {
		if ( null === $this->expiry_actions || ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			return;
		}

		new ContentExpiredTrigger();
		new ContentScheduledTrigger();
		new UnpublishPostAction( $this->expiry_actions );
		new RedirectPostAction( $this->expiry_actions );
	}
}
