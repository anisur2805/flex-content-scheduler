<?php

use Anisur\ContentScheduler\Api\ScheduleRestController;
use PHPUnit\Framework\TestCase;

class RestApiTest extends TestCase {
	public function test_controller_has_route_registration_method(): void {
		$this->assertTrue( method_exists( ScheduleRestController::class, 'register_routes' ) );
	}
}
