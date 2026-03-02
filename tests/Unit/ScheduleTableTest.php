<?php

use Anisur\ContentScheduler\Database\ScheduleTable;
use PHPUnit\Framework\TestCase;

class FLEX_CS_WPDB_Table_Stub {
	public string $prefix = 'wp_';
	public string $last_query = '';

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4';
	}

	public function query( string $query ): bool {
		$this->last_query = $query;
		return true;
	}
}

class ScheduleTableTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wpdb'] = new FLEX_CS_WPDB_Table_Stub();
		$GLOBALS['flex_cs_dbdelta_sql'] = '';
	}

	public function test_get_table_name_uses_wp_prefix(): void {
		$this->assertSame( 'wp_content_schedules', ScheduleTable::get_table_name() );
	}

	public function test_create_table_calls_dbdelta_with_expected_sql(): void {
		$table = new ScheduleTable();
		$table->create_table();

		$this->assertStringContainsString( 'CREATE TABLE wp_content_schedules', $GLOBALS['flex_cs_dbdelta_sql'] );
	}

	public function test_drop_table_runs_drop_query(): void {
		$table = new ScheduleTable();
		$table->drop_table();

		$this->assertStringContainsString( 'DROP TABLE IF EXISTS `wp_content_schedules`', $GLOBALS['wpdb']->last_query );
	}
}
