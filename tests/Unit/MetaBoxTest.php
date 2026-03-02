<?php

use Anisur\ContentScheduler\Admin\MetaBox;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;
use PHPUnit\Framework\TestCase;

class MetaBoxScheduleManagerStub extends ScheduleManager {
	public array $calls = array();
	public $existing = null;

	public function __construct() {
	}

	public function get_schedule_by_post( int $post_id ) {
		return $this->existing;
	}

	public function create_schedule( array $data ) {
		$this->calls[] = array( 'method' => 'create', 'data' => $data );
		return 101;
	}

	public function update_schedule( int $schedule_id, array $data ): bool {
		$this->calls[] = array( 'method' => 'update', 'id' => $schedule_id, 'data' => $data );
		return true;
	}

	public function delete_schedule( int $schedule_id ): bool {
		$this->calls[] = array( 'method' => 'delete', 'id' => $schedule_id );
		return true;
	}
}

class MetaBoxTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$_POST = array();
		$GLOBALS['flex_cs_user_caps']['edit_post'] = true;
	}

	public function test_save_meta_box_data_creates_schedule_when_none_exists(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$box     = new MetaBox( $manager );

		$_POST['flex_cs_metabox_nonce'] = 'valid-nonce';
		$_POST['flex_cs_expiry_action'] = 'unpublish';
		$_POST['flex_cs_expiry_date']   = '2026-03-03T10:00';

		$box->save_meta_box_data( 12 );

		$this->assertSame( 'create', $manager->calls[0]['method'] );
		$this->assertSame( 12, $manager->calls[0]['data']['post_id'] );
	}

	public function test_save_meta_box_data_updates_existing_schedule(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$manager->existing = (object) array( 'id' => 77 );
		$box = new MetaBox( $manager );

		$_POST['flex_cs_metabox_nonce'] = 'valid-nonce';
		$_POST['flex_cs_expiry_action'] = 'change_status';
		$_POST['flex_cs_expiry_date']   = '2026-03-03T10:00';
		$_POST['flex_cs_new_status']    = 'private';

		$box->save_meta_box_data( 12 );

		$this->assertSame( 'update', $manager->calls[0]['method'] );
		$this->assertSame( 77, $manager->calls[0]['id'] );
	}

	public function test_save_meta_box_data_deletes_schedule_when_requested(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$manager->existing = (object) array( 'id' => 88 );
		$box = new MetaBox( $manager );

		$_POST['flex_cs_metabox_nonce'] = 'valid-nonce';
		$_POST['flex_cs_expiry_action'] = 'unpublish';
		$_POST['flex_cs_expiry_date']   = '2026-03-03T10:00';
		$_POST['flex_cs_delete_schedule'] = '1';

		$box->save_meta_box_data( 12 );

		$this->assertSame( 'delete', $manager->calls[0]['method'] );
		$this->assertSame( 88, $manager->calls[0]['id'] );
	}

	public function test_save_inline_edit_data_creates_schedule_when_none_exists(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$box     = new MetaBox( $manager );

		$_POST['flex_cs_inline_nonce']        = 'valid-nonce';
		$_POST['flex_cs_inline_expiry_action'] = 'unpublish';
		$_POST['flex_cs_inline_expiry_date']   = '2026-03-03T10:00';

		$box->save_inline_edit_data( 15 );

		$this->assertSame( 'create', $manager->calls[0]['method'] );
		$this->assertSame( 15, $manager->calls[0]['data']['post_id'] );
	}

	public function test_save_inline_edit_data_updates_existing_schedule(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$manager->existing = (object) array( 'id' => 42 );
		$box = new MetaBox( $manager );

		$_POST['flex_cs_inline_nonce']         = 'valid-nonce';
		$_POST['flex_cs_inline_expiry_action'] = 'change_status';
		$_POST['flex_cs_inline_expiry_date']   = '2026-03-03T10:00';
		$_POST['flex_cs_inline_new_status']    = 'private';

		$box->save_inline_edit_data( 15 );

		$this->assertSame( 'update', $manager->calls[0]['method'] );
		$this->assertSame( 42, $manager->calls[0]['id'] );
	}

	public function test_save_inline_edit_data_deletes_schedule_when_requested(): void {
		$manager = new MetaBoxScheduleManagerStub();
		$manager->existing = (object) array( 'id' => 52 );
		$box = new MetaBox( $manager );

		$_POST['flex_cs_inline_nonce'] = 'valid-nonce';
		$_POST['flex_cs_inline_remove'] = '1';

		$box->save_inline_edit_data( 15 );

		$this->assertSame( 'delete', $manager->calls[0]['method'] );
		$this->assertSame( 52, $manager->calls[0]['id'] );
	}
}
