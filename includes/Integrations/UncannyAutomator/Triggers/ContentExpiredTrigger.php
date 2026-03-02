<?php
/**
 * Automator trigger: Content expired.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Integrations\UncannyAutomator\Triggers;

/**
 * Class ContentExpiredTrigger
 *
 * Fires an Automator trigger when a schedule's expiry action completes successfully.
 *
 * @since 1.0.0
 */
class ContentExpiredTrigger {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'flex_cs_after_expiry_action', array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Handle the after-expiry-action hook.
	 *
	 * @since 1.0.0
	 *
	 * @param object $schedule Schedule row object.
	 * @param bool   $result   Whether the expiry action succeeded.
	 * @return void
	 */
	public function handle( object $schedule, bool $result ): void {
		if ( ! $result ) {
			return;
		}

		do_action(
			'flex_cs_automator_trigger',
			'FLEX_CS_CONTENT_EXPIRED',
			array(
				'POST_ID'       => (int) $schedule->post_id,
				'POST_TITLE'    => get_the_title( (int) $schedule->post_id ),
				'POST_TYPE'     => get_post_type( (int) $schedule->post_id ),
				'EXPIRY_ACTION' => (string) $schedule->expiry_action,
				'EXPIRY_DATE'   => isset( $schedule->expiry_date ) ? (string) $schedule->expiry_date : '',
			)
		);
	}
}
