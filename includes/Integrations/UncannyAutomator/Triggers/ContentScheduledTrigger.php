<?php
/**
 * Automator trigger: Content scheduled.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers;

/**
 * Class ContentScheduledTrigger
 *
 * Fires an Automator trigger when a new content schedule is created.
 *
 * @since 1.0.0
 */
class ContentScheduledTrigger {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'flex_cs_schedule_created', array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Handle the schedule-created hook.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $schedule_id Schedule ID.
	 * @param array $data        Schedule data.
	 * @return void
	 */
	public function handle( int $schedule_id, array $data ): void {
		do_action(
			'flex_cs_automator_trigger',
			'FLEX_CS_CONTENT_SCHEDULED',
			array(
				'SCHEDULE_ID'   => $schedule_id,
				'POST_ID'       => (int) ( $data['post_id'] ?? 0 ),
				'EXPIRY_DATE'   => (string) ( $data['expiry_date'] ?? '' ),
				'EXPIRY_ACTION' => (string) ( $data['expiry_action'] ?? '' ),
			)
		);
	}
}
