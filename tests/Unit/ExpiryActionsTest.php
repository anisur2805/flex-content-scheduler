<?php

use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use PHPUnit\Framework\TestCase;

class ExpiryActionsRedirectTestDouble extends ExpiryActions {
	public string $redirected_to = '';

	protected function perform_redirect( string $redirect_url ): void {
		$this->redirected_to = $redirect_url;
	}
}

class ExpiryActionsTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_actions_fired'] = array();
		$GLOBALS['flex_cs_stub_post_exists'] = true;
		$GLOBALS['flex_cs_deleted_meta'] = array();
		$GLOBALS['flex_cs_is_admin'] = false;
		$GLOBALS['flex_cs_is_singular'] = true;
		$GLOBALS['flex_cs_queried_object_id'] = 0;
		$GLOBALS['flex_cs_post_meta'] = array();
		$GLOBALS['flex_cs_sticky_posts'] = array();
		$GLOBALS['flex_cs_options']['flex_cs_settings']['allowed_redirect_hosts'] = array();
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
		$this->assertSame( '_flex_cs_redirect_url', $GLOBALS['flex_cs_deleted_meta'][0]['meta_key'] );
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
		$this->assertSame( '_flex_cs_redirect_url', $GLOBALS['flex_cs_deleted_meta'][0]['meta_key'] );
	}

	public function test_process_calls_sticky_when_action_is_sticky(): void {
		$actions = new ExpiryActions();
		$result  = $actions->process(
			(object) array(
				'post_id'       => 21,
				'expiry_action' => 'sticky',
			)
		);

		$this->assertTrue( $result );
		$this->assertContains( 21, $GLOBALS['flex_cs_sticky_posts'] );
	}

	public function test_process_calls_unsticky_when_action_is_unsticky(): void {
		$GLOBALS['flex_cs_sticky_posts'] = array( 21 );

		$actions = new ExpiryActions();
		$result  = $actions->process(
			(object) array(
				'post_id'       => 21,
				'expiry_action' => 'unsticky',
			)
		);

		$this->assertTrue( $result );
		$this->assertNotContains( 21, $GLOBALS['flex_cs_sticky_posts'] );
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

		$hooks = array_column( $GLOBALS['flex_cs_actions_fired'], 'hook' );
		$this->assertContains( 'flex_cs_before_expiry_action', $hooks );
		$this->assertContains( 'flex_cs_after_expiry_action', $hooks );
	}

	public function test_handle_template_redirect_redirects_when_host_is_allowed(): void {
		$actions = new ExpiryActionsRedirectTestDouble();

		$GLOBALS['flex_cs_queried_object_id'] = 12;
		$GLOBALS['flex_cs_post_meta'][12]['_flex_cs_redirect_url'] = 'https://example.com/target';
		$GLOBALS['flex_cs_options']['flex_cs_settings']['allowed_redirect_hosts'] = array( 'example.com' );

		$actions->handle_template_redirect();

		$this->assertSame( 'https://example.com/target', $actions->redirected_to );
	}

	public function test_handle_template_redirect_skips_when_host_not_allowed(): void {
		$actions = new ExpiryActionsRedirectTestDouble();

		$GLOBALS['flex_cs_queried_object_id'] = 12;
		$GLOBALS['flex_cs_post_meta'][12]['_flex_cs_redirect_url'] = 'https://blocked.test/target';
		$GLOBALS['flex_cs_options']['flex_cs_settings']['allowed_redirect_hosts'] = array( 'example.com' );

		$actions->handle_template_redirect();

		$this->assertSame( '', $actions->redirected_to );
	}
}
