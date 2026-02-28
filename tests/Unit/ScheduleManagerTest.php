<?php

use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class FLEX_CS_WPDB_Stub {
	public int $insert_id = 0;
	public array $last_insert = array();
	public array $last_update = array();
	public string $last_query = '';
	public string $posts = 'wp_posts';
	public string $prefix = 'wp_';

	public function insert( $table, $data, $format ) {
		$this->last_insert = compact( 'table', 'data', 'format' );
		$this->insert_id   = 10;
		return 1;
	}

	public function update( $table, $data, $where, $format, $where_format ) {
		$this->last_update = compact( 'table', 'data', 'where', 'format', 'where_format' );
		return 1;
	}

	public function delete( $table, $where, $where_format ) {
		return 1;
	}

	public function get_row( $query ) {
		$this->last_query = $query;

		if ( false !== strpos( $query, 'WHERE id = 999' ) ) {
			return null;
		}

		return (object) array(
			'id' => 1,
			'post_id' => 5,
		);
	}

	public function get_results( $query ) {
		$this->last_query = $query;
		return array(
			(object) array(
				'id' => 1,
				'is_processed' => 0,
			),
		);
	}

	public function get_var( $query ) {
		$this->last_query = $query;
		return 1;
	}

	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) {
			$query = preg_replace( '/%[ds]/', is_numeric( $arg ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'", $query, 1 );
		}
		return $query;
	}

	public function suppress_errors( $suppress = null ) {
		static $is_suppressed = false;

		if ( null === $suppress ) {
			return $is_suppressed;
		}

		$previous      = $is_suppressed;
		$is_suppressed = (bool) $suppress;

		return $previous;
	}
}

class ScheduleManagerForUpdateTest extends ScheduleManager {
	public function __construct() {
	}

	public function get_schedule( int $schedule_id ) {
		if ( 999 === $schedule_id ) {
			return null;
		}

		return (object) array(
			'id' => $schedule_id,
			'post_id' => 5,
		);
	}
}

class ScheduleManagerTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wpdb']                 = new FLEX_CS_WPDB_Stub();
		$GLOBALS['fcs_actions_fired']   = array();
		$GLOBALS['fcs_stub_post_exists'] = true;
	}

	private function valid_payload(): array {
		return array(
			'post_id'       => 5,
			'expiry_date'   => '2026-12-31 10:00:00',
			'expiry_action' => 'unpublish',
		);
	}

	public function test_create_schedule_returns_integer_on_success(): void {
		$manager = new ScheduleManager();
		$id      = $manager->create_schedule( $this->valid_payload() );

		$this->assertSame( 10, $id );
	}

	public function test_create_schedule_returns_false_with_missing_post_id(): void {
		$manager = new ScheduleManager();

		$result = $manager->create_schedule(
			array(
				'expiry_date'   => '2026-12-31 10:00:00',
				'expiry_action' => 'unpublish',
			)
		);

		$this->assertFalse( $result );
	}

	public function test_create_schedule_returns_false_with_invalid_date(): void {
		$manager = new ScheduleManager();

		$result = $manager->create_schedule(
			array(
				'post_id'       => 5,
				'expiry_date'   => 'bad-date',
				'expiry_action' => 'unpublish',
			)
		);

		$this->assertFalse( $result );
	}

	public function test_create_schedule_returns_false_with_invalid_action(): void {
		$manager = new ScheduleManager();

		$result = $manager->create_schedule(
			array(
				'post_id'       => 5,
				'expiry_date'   => '2026-12-31 10:00:00',
				'expiry_action' => 'invalid_action',
			)
		);

		$this->assertFalse( $result );
	}

	public function test_create_schedule_returns_false_with_missing_redirect_url_when_action_is_redirect(): void {
		$manager = new ScheduleManager();

		$result = $manager->create_schedule(
			array(
				'post_id'       => 5,
				'expiry_date'   => '2026-12-31 10:00:00',
				'expiry_action' => 'redirect',
			)
		);

		$this->assertFalse( $result );
	}

	public function test_update_schedule_returns_true_on_success(): void {
		$manager = new ScheduleManagerForUpdateTest();

		$this->assertTrue( $manager->update_schedule( 1, $this->valid_payload() ) );
	}

	public function test_update_schedule_returns_false_on_nonexistent_id(): void {
		$manager = new ScheduleManagerForUpdateTest();

		$this->assertFalse( $manager->update_schedule( 999, $this->valid_payload() ) );
	}

	public function test_delete_schedule_returns_true_on_success(): void {
		$manager = new ScheduleManager();

		$this->assertTrue( $manager->delete_schedule( 1 ) );
	}

	public function test_get_due_schedules_returns_array(): void {
		$manager = new ScheduleManager();

		$this->assertIsArray( $manager->get_due_schedules() );
	}

	public function test_get_due_schedules_excludes_processed_items(): void {
		$manager = new ScheduleManager();
		$manager->get_due_schedules();

		$this->assertStringContainsString( 'is_processed = 0', $GLOBALS['wpdb']->last_query );
	}

	public function test_mark_processed_sets_is_processed_to_one(): void {
		$manager = new ScheduleManager();

		$this->assertTrue( $manager->mark_processed( 1 ) );
		$this->assertSame( 1, $GLOBALS['wpdb']->last_update['data']['is_processed'] );
	}
}
