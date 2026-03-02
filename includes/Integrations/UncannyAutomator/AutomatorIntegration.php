<?php
/**
 * Uncanny Automator integration bootstrap.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator;

use Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions\RedirectPostAction;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Actions\UnpublishPostAction;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers\ContentExpiredTrigger;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers\ContentScheduledTrigger;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;

/**
 * Class AutomatorIntegration
 *
 * Registers Flex Content Scheduler triggers and actions with Uncanny Automator.
 *
 * @since 1.0.0
 */
class AutomatorIntegration {
	/**
	 * Expiry actions handler.
	 *
	 * @since 1.0.0
	 * @var ExpiryActions|null
	 */
	private ?ExpiryActions $expiry_actions = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ExpiryActions $expiry_actions Expiry actions handler.
	 */
	public function __construct( ExpiryActions $expiry_actions ) {
		if ( ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			return;
		}

		$this->expiry_actions = $expiry_actions;
	}

	/**
	 * Register all Automator triggers and actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
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
