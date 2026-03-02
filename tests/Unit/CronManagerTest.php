<?php

use Anisur\ContentScheduler\Scheduler\CronManager;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class CronManagerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_actions_fired'] = array();
		$GLOBALS['flex_cs_sent_emails']   = array();
		$GLOBALS['flex_cs_options']['flex_cs_settings'] = array(
			'default_action'         => 'unpublish',
			'cron_enabled'           => true,
			'notification_email'     => '',
			'allowed_redirect_hosts' => array(),
		);
	}

	public function test_custom_interval_is_registered(): void {
		$manager   = new CronManager( $this->createMock( ScheduleManager::class ), $this->createMock( ExpiryActions::class ) );
		$schedules = $manager->register_custom_interval( array() );

		$this->assertArrayHasKey( 'every_5_minutes', $schedules );
		$this->assertSame( 300, $schedules['every_5_minutes']['interval'] );
	}

	public function test_process_due_schedules_calls_expiry_actions_for_each_due_item(): void {
		$schedule_one = (object) array( 'id' => 1 );
		$schedule_two = (object) array( 'id' => 2 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->expects( $this->exactly( 2 ) )
			->method( 'get_due_schedules' )
			->willReturnOnConsecutiveCalls( array( $schedule_one, $schedule_two ), array() );
		$schedule_manager->expects( $this->exactly( 2 ) )
			->method( 'mark_processed' )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->expects( $this->exactly( 2 ) )
			->method( 'process' )
			->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();

		$this->assertSame( 'flex_cs_cron_processed', end( $GLOBALS['flex_cs_actions_fired'] )['hook'] );
	}

	public function test_process_due_schedules_marks_item_as_processed_on_success(): void {
		$schedule = (object) array(
			'id'            => 42,
			'post_id'       => 42,
			'expiry_action' => 'unpublish',
			'expiry_date'   => '2026-03-02 00:00:00',
		);

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->method( 'get_due_schedules' )
			->willReturnOnConsecutiveCalls( array( $schedule ), array() );
		$schedule_manager->expects( $this->once() )
			->method( 'mark_processed' )
			->with( 42 )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();
	}

	public function test_process_due_schedules_sends_notification_on_success_when_email_configured(): void {
		$GLOBALS['flex_cs_options']['flex_cs_settings']['notification_email'] = 'owner@example.com';

		$schedule = (object) array(
			'id'            => 20,
			'post_id'       => 88,
			'expiry_action' => 'redirect',
			'expiry_date'   => '2026-03-02 00:00:00',
		);

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->method( 'get_due_schedules' )
			->willReturnOnConsecutiveCalls( array( $schedule ), array() );
		$schedule_manager->expects( $this->once() )
			->method( 'mark_processed' )
			->with( 20 )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();

		$this->assertCount( 1, $GLOBALS['flex_cs_sent_emails'] );
		$this->assertSame( 'owner@example.com', $GLOBALS['flex_cs_sent_emails'][0]['to'] );
	}

	public function test_process_due_schedules_does_not_mark_processed_on_failure(): void {
		$GLOBALS['flex_cs_options']['flex_cs_settings']['notification_email'] = 'owner@example.com';
		$schedule = (object) array( 'id' => 42 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->method( 'get_due_schedules' )
			->willReturnOnConsecutiveCalls( array( $schedule ), array() );
		$schedule_manager->expects( $this->never() )->method( 'mark_processed' );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( false );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();

		$this->assertCount( 0, $GLOBALS['flex_cs_sent_emails'] );
	}
}
