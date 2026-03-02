<?php

use Anisur\ContentScheduler\Integrations\UncannyAutomator\AutomatorIntegration;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use PHPUnit\Framework\TestCase;

class AutomatorIntegrationTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_actions'] = array();

		if ( ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			eval( 'namespace Uncanny_Automator; class Automator_Load {}' );
		}
	}

	public function test_register_adds_trigger_and_action_hooks_when_automator_is_available(): void {
		$integration = new AutomatorIntegration( $this->createMock( ExpiryActions::class ) );
		$integration->register();

		$this->assertArrayHasKey( 'flex_cs_after_expiry_action', $GLOBALS['flex_cs_actions'] );
		$this->assertArrayHasKey( 'flex_cs_schedule_created', $GLOBALS['flex_cs_actions'] );
		$this->assertArrayHasKey( 'flex_cs_automator_action_unpublish', $GLOBALS['flex_cs_actions'] );
	}
}
