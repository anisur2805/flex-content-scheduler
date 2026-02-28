<?php

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers;

class ContentExpiredTrigger {
	public function __construct() {
		add_action( 'fcs_after_expiry_action', array( $this, 'handle' ), 10, 2 );
	}

	public function handle( object $schedule, bool $result ): void {
		if ( ! $result ) {
			return;
		}

		do_action(
			'fcs_automator_trigger',
			'FCS_CONTENT_EXPIRED',
			array(
				'POST_ID'       => (int) $schedule->post_id,
				'POST_TITLE'    => get_the_title( (int) $schedule->post_id ),
				'POST_TYPE'     => get_post_type( (int) $schedule->post_id ),
				'EXPIRY_ACTION' => (string) $schedule->expiry_action,
				'EXPIRY_DATE'   => (string) $schedule->expiry_date,
			)
		);
	}
}
