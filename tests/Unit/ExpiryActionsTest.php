<?php

use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use PHPUnit\Framework\TestCase;

class ExpiryActionsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fcs_actions_fired'] = array();
		$GLOBALS['fcs_stub_post_exists'] = true;
		$GLOBALS['fcs_deleted_meta'] = array();
	}

	public function test_process_calls_unpublish_when_action_is_unpublish(): void {
		$actions = new ExpiryActions();
		$result  = $actions->process(
			(object) array(
				'post_id'       => 5,
				'expiry_action' => 'unpublish',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '_fcs_redirect_url', $GLOBALS['fcs_deleted_meta'][0]['meta_key'] );
	}

	public function test_process_calls_delete_when_action_is_delete(): void {
		$actions = $this->getMockBuilder( ExpiryActions::class )
			->onlyMethods( array( 'delete_post' ) )
			->getMock();

		$actions->expects( $this->once() )->method( 'delete_post' )->with( 5 )->willReturn( true );

		$result = $actions->process(
			(object) array(
				'post_id'       => 5,
				'expiry_action' => 'delete',
			)
		);

		$this->assertTrue( $result );
	}

	public function test_process_calls_redirect_when_action_is_redirect(): void {
		$actions = $this->getMockBuilder( ExpiryActions::class )
			->onlyMethods( array( 'redirect' ) )
			->getMock();

		$actions->expects( $this->once() )->method( 'redirect' )->with( 5, 'https://example.com' )->willReturn( true );

		$result = $actions->process(
			(object) array(
				'post_id'       => 5,
				'expiry_action' => 'redirect',
				'redirect_url'  => 'https://example.com',
			)
		);

		$this->assertTrue( $result );
	}

	public function test_process_calls_change_status_when_action_is_change_status(): void {
		$actions = new ExpiryActions();
		$result  = $actions->process(
			(object) array(
				'post_id'       => 5,
				'expiry_action' => 'change_status',
				'new_status'    => 'private',
			)
		);

		$this->assertTrue( $result );
		$this->assertSame( '_fcs_redirect_url', $GLOBALS['fcs_deleted_meta'][0]['meta_key'] );
	}

	public function test_process_returns_false_on_invalid_post_id(): void {
		$actions = new ExpiryActions();

		$result = $actions->process(
			(object) array(
				'post_id'       => 0,
				'expiry_action' => 'unpublish',
			)
		);

		$this->assertFalse( $result );
	}

	public function test_process_fires_before_and_after_action_hooks(): void {
		$actions = new ExpiryActions();

		$actions->process(
			(object) array(
				'post_id'       => 7,
				'expiry_action' => 'unpublish',
			)
		);

		$hooks = array_column( $GLOBALS['fcs_actions_fired'], 'hook' );
		$this->assertContains( 'fcs_before_expiry_action', $hooks );
		$this->assertContains( 'fcs_after_expiry_action', $hooks );
	}
}
