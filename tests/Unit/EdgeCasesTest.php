<?php

use Anisur\ContentScheduler\Scheduler\CronManager;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class EdgeCaseWpdbStub {
	public int $insert_id = 0;
	public string $prefix = 'wp_';
	public string $posts = 'wp_posts';
	public string $last_error = 'insert failed';

	public function suppress_errors( $suppress = null ) {
		return false;
	}

	public function get_var( $query ) {
		return 'wp_content_schedules';
	}

	public function insert( $table, $data, $format ) {
		return false;
	}

	public function prepare( $query, ...$args ) {
		return $query;
	}

	public function query( $query ) {
		return false;
	}
}

class EdgeCasesTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_options']['flex_cs_settings'] = array(
			'default_action' => 'unpublish',
			'cron_enabled' => true,
			'allowed_redirect_hosts' => array(),
		);
	}

	public function test_schedule_manager_create_returns_wp_error_on_db_failure(): void {
		$GLOBALS['wpdb'] = new EdgeCaseWpdbStub();
		$manager         = new ScheduleManager();

		$result = $manager->create_schedule(
			array(
				'post_id' => 5,
				'expiry_date' => '2026-12-31 10:00:00',
				'expiry_action' => 'unpublish',
			)
		);

		$this->assertFalse( $result );
		$this->assertInstanceOf( WP_Error::class, $manager->get_last_error() );
	}

	public function test_cron_manager_stops_batch_processing_after_max_iterations(): void {
		$schedule = (object) array( 'id' => 1 );

		$schedule_manager = $this->createMock( ScheduleManager::class );
		$schedule_manager->expects( $this->exactly( 10 ) )
			->method( 'get_due_schedules' )
			->willReturn( array( $schedule ) );
		$schedule_manager->expects( $this->exactly( 10 ) )
			->method( 'mark_processed' )
			->willReturn( true );

		$expiry_actions = $this->createMock( ExpiryActions::class );
		$expiry_actions->method( 'process' )->willReturn( true );

		$manager = new CronManager( $schedule_manager, $expiry_actions );
		$manager->process_due_schedules();
	}
}
