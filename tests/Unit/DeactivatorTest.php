<?php

use Anisur\ContentScheduler\Deactivator;
use PHPUnit\Framework\TestCase;

class DeactivatorTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_cleared_hooks'] = array();
		$GLOBALS['flex_cs_rewrite_flushed'] = 0;
	}

	public function test_deactivate_clears_cron_hook_and_flushes_rewrites(): void {
		Deactivator::deactivate();

		$this->assertContains( 'flex_cs_process_schedules', $GLOBALS['flex_cs_cleared_hooks'] );
		$this->assertGreaterThan( 0, $GLOBALS['flex_cs_rewrite_flushed'] );
	}
}
