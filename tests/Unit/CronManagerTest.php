<?php

use Anisur\ContentScheduler\Scheduler\CronManager;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class CronManagerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fcs_actions_fired'] = array();
	}

	public function test_custom_interval_is_registered(): void {
		$manager   = new CronManager( $this->createMock( ScheduleManager::class ), $this->createMock( ExpiryActions::class ) );
		$schedules = $manager->register_custom_interval( array() );

		$this->assertArrayHasKey( 'every_minute', $schedules );
		$this->assertSame( 60, $schedules['every_minute']['interval'] );
	}

	public function test_process_due_schedules_calls_expiry_actions_for_each_due_item(): void {
		$schedule_one = (object) array( 'id' => 1 );
		$schedule_two = (object) array( 'id' => 2 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->expects( $this->once() )
			->method( 'get_due_schedules' )
			->willReturn( array( $schedule_one, $schedule_two ) );
		$schedule_manager->expects( $this->exactly( 2 ) )
			->method( 'mark_processed' )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->expects( $this->exactly( 2 ) )
			->method( 'process' )
			->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();

		$this->assertSame( 'fcs_cron_processed', end( $GLOBALS['fcs_actions_fired'] )['hook'] );
	}

	public function test_process_due_schedules_marks_item_as_processed_on_success(): void {
		$schedule = (object) array( 'id' => 42 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->method( 'get_due_schedules' )->willReturn( array( $schedule ) );
		$schedule_manager->expects( $this->once() )
			->method( 'mark_processed' )
			->with( 42 )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();
	}

	public function test_process_due_schedules_does_not_mark_processed_on_failure(): void {
		$schedule = (object) array( 'id' => 42 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->method( 'get_due_schedules' )->willReturn( array( $schedule ) );
		$schedule_manager->expects( $this->never() )->method( 'mark_processed' );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( false );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();
	}
}
