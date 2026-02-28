<?php

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers;

class ContentScheduledTrigger {
	public function __construct() {
		add_action( 'flex_cs_schedule_created', array( $this, 'handle' ), 10, 2 );
	}

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
