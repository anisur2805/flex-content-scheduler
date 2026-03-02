<?php
/**
 * Database migration manager.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Database;

/**
 * Class MigrationManager
 *
 * Runs versioned database migrations for the plugin schema.
 *
 * @since 1.0.0
 */
class MigrationManager {
	/**
	 * Option key used to store current DB schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $db_version_option = 'flex_cs_db_version';

	/**
	 * Target schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $target_version = '1.1.0';

	/**
	 * Run pending migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function migrate(): void {
		$current_version = (string) get_option( $this->db_version_option, '0.0.0' );

		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			$table = new ScheduleTable();
			$table->create_table();
		}

		update_option( $this->db_version_option, $this->target_version, false );
	}
}
