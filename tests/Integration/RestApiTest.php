<?php

use Anisur\ContentScheduler\Api\ScheduleRestController;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class RestApiTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_filters']         = array();
		$GLOBALS['flex_cs_registered_routes'] = array();
		$GLOBALS['flex_cs_user_caps']       = array(
			'edit_posts' => true,
			'manage_options' => true,
			'edit_post' => true,
		);
		$GLOBALS['flex_cs_current_user_id'] = 1;
		$GLOBALS['flex_cs_transients']      = array();
	}

	public function test_register_routes_adds_expected_routes(): void {
		$manager    = $this->createMock( ScheduleManager::class );
		$controller = new ScheduleRestController( $manager );

		$controller->register_routes();

		$this->assertNotEmpty( $GLOBALS['flex_cs_registered_routes'] );
	}

	public function test_get_items_returns_response_with_total_header(): void {
		$manager = $this->createMock( ScheduleManager::class );
		$manager->method( 'get_all_schedules' )->willReturn( array( array( 'id' => 1 ) ) );
		$manager->method( 'count_schedules' )->willReturn( 12 );

		$controller = new ScheduleRestController( $manager );
		$request    = new WP_REST_Request( 'GET', array( 'per_page' => 20, 'page' => 1 ) );
		$response   = $controller->get_items( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( '12', $response->get_headers()['X-WP-Total'] );
	}

	public function test_create_item_permissions_check_rejects_missing_nonce(): void {
		$manager    = $this->createMock( ScheduleManager::class );
		$controller = new ScheduleRestController( $manager );
		$request    = new WP_REST_Request( 'POST', array(), array( 'post_id' => 5 ), array() );

		$result = $controller->create_item_permissions_check( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	public function test_create_item_permissions_check_applies_rate_limit(): void {
		$manager    = $this->createMock( ScheduleManager::class );
		$controller = new ScheduleRestController( $manager );
		$request    = new WP_REST_Request(
			'POST',
			array(),
			array( 'post_id' => 5 ),
			array( 'x-wp-nonce' => 'valid-nonce' )
		);

		add_filter(
			'flex_cs_rest_write_rate_limit',
			static function () {
				return 1;
			}
		);

		$first = $controller->create_item_permissions_check( $request );
		$second = $controller->create_item_permissions_check( $request );

		$this->assertTrue( $first );
		$this->assertInstanceOf( WP_Error::class, $second );
		$this->assertSame( 'flex_cs_rate_limited', $second->get_error_code() );
	}
}
