<?php

use Anisur\ContentScheduler\Admin\AdminMenu;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class AdminMenuTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flex_cs_management_pages'] = array();
		$GLOBALS['flex_cs_enqueued_scripts'] = array();
		$GLOBALS['flex_cs_enqueued_styles'] = array();
		$GLOBALS['flex_cs_localized_scripts'] = array();
	}

	public function test_add_menu_page_registers_tools_page(): void {
		$menu = new AdminMenu( $this->createMock( ScheduleManager::class ) );
		$menu->add_menu_page();

		$this->assertNotEmpty( $GLOBALS['flex_cs_management_pages'] );
		$this->assertSame( 'flex-cs-schedules', $GLOBALS['flex_cs_management_pages'][0]['menu_slug'] );
	}

	public function test_enqueue_scripts_localizes_admin_data_on_target_hook(): void {
		$menu = new AdminMenu( $this->createMock( ScheduleManager::class ) );
		$menu->enqueue_scripts( 'tools_page_flex-cs-schedules' );

		$this->assertNotEmpty( $GLOBALS['flex_cs_enqueued_scripts'] );
		$this->assertNotEmpty( $GLOBALS['flex_cs_localized_scripts'] );
		$this->assertSame( 'flex-cs-admin', $GLOBALS['flex_cs_localized_scripts'][0]['handle'] );
	}
}
