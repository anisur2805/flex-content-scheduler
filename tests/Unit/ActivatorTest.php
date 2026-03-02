<?php

use Anisur\ContentScheduler\Activator;
use PHPUnit\Framework\TestCase;

class ActivatorWpdbStub {
	public string $prefix = 'wp_';

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4';
	}
}

class ActivatorTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_version'] = '6.0';
		$GLOBALS['wpdb'] = new ActivatorWpdbStub();
		$GLOBALS['flex_cs_scheduled_events'] = array();
		$GLOBALS['flex_cs_rewrite_flushed'] = 0;
		$GLOBALS['flex_cs_options'] = array();
	}

	public function test_add_every_5_minutes_schedule_registers_interval(): void {
		$schedules = Activator::add_every_5_minutes_schedule( array() );
		$this->assertArrayHasKey( 'every_5_minutes', $schedules );
		$this->assertSame( 300, $schedules['every_5_minutes']['interval'] );
	}

	public function test_activate_sets_version_and_schedules_event(): void {
		Activator::activate();

		$this->assertSame( '1.0.0', $GLOBALS['flex_cs_options']['flex_cs_version'] );
		$this->assertNotEmpty( $GLOBALS['flex_cs_scheduled_events'] );
		$this->assertGreaterThan( 0, $GLOBALS['flex_cs_rewrite_flushed'] );
	}
}
