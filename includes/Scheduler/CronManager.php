<?php
/**
 * Cron management for schedule processing.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler\Scheduler;

use Anisur\ContentScheduler\Helpers\Logger;
use Anisur\ContentScheduler\Loader;

/**
 * Class CronManager
 *
 * Handles cron scheduling and processing of expired schedules.
 *
 * @since 1.0.0
 */
class CronManager {
	/**
	 * Schedule manager instance.
	 *
	 * @since 1.0.0
	 * @var ScheduleManager
	 */
	private ScheduleManager $schedule_manager;

	/**
	 * Expiry actions handler.
	 *
	 * @since 1.0.0
	 * @var ExpiryActions
	 */
	private ExpiryActions $expiry_actions;

	/**
	 * Runtime lock option name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $runtime_lock_option = 'flex_cs_last_runtime_process';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ScheduleManager $schedule_manager Schedule manager instance.
	 * @param ExpiryActions   $expiry_actions   Expiry actions handler.
	 */
	public function __construct( ScheduleManager $schedule_manager, ExpiryActions $expiry_actions ) {
		$this->schedule_manager = $schedule_manager;
		$this->expiry_actions   = $expiry_actions;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param Loader $loader Hook loader instance.
	 * @return void
	 */
	public function register_hooks( Loader $loader ): void {
		$loader->add_filter( 'cron_schedules', $this, 'register_custom_interval' );
		$loader->add_action( 'flex_cs_process_schedules', $this, 'process_due_schedules', 10, 0 );
		$loader->add_action( 'init', $this, 'ensure_cron_event_scheduled', 20, 0 );
	}

	/**
	 * Register the every-minute cron interval.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
	public function register_custom_interval( array $schedules ): array {
		$schedules['every_5_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'flex-content-scheduler' ),
		);

		return $schedules;
	}

	/**
	 * Process all due schedules in batches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function process_due_schedules(): void {
		$settings = get_option( 'flex_cs_settings', array() );
		if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
			/**
			 * Fires after cron processing completes.
			 *
			 * @since 1.0.0
			 *
			 * @param int $processed_count Number of processed schedules.
			 */
			do_action( 'flex_cs_cron_processed', 0 );
			return;
		}

		$processed_count = 0;
		$max_iterations  = 10; // Prevent infinite loops.
		$iteration       = 0;

		// Process in batches until no more due schedules.
		do {
			$due_schedules = $this->schedule_manager->get_due_schedules();
			$batch_count   = count( $due_schedules );

			if ( 0 === $batch_count ) {
				break;
			}

			foreach ( $due_schedules as $schedule ) {
				$result = $this->expiry_actions->process( $schedule );

				if ( $result ) {
					$this->schedule_manager->mark_processed( (int) $schedule->id );
					$processed_count++;
					continue;
				}

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					Logger::debug( sprintf( 'Failed processing schedule ID %d', (int) $schedule->id ) );
				}
			}

			$iteration++;
		} while ( $iteration < $max_iterations );

		/**
		 * Fires after cron processing completes.
		 *
		 * @since 1.0.0
		 *
		 * @param int $processed_count Number of processed schedules.
		 */
		do_action( 'flex_cs_cron_processed', $processed_count );
	}

	/**
	 * Ensure the cron event is scheduled if cron is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ensure_cron_event_scheduled(): void {
		$settings = get_option( 'flex_cs_settings', array() );
		if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
			wp_clear_scheduled_hook( 'flex_cs_process_schedules' );
			return;
		}

		if ( ! wp_next_scheduled( 'flex_cs_process_schedules' ) ) {
			wp_schedule_event( time() + 300, 'every_5_minutes', 'flex_cs_process_schedules' );
		}
	}

	/**
	 * Process due schedules during a regular page load as a fallback when cron is unreliable.
	 *
	 * Runs at most once per minute, skipped on cron and REST requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_process_due_schedules_runtime(): void {
		if ( ! apply_filters( 'flex_cs_enable_runtime_fallback', false ) ) {
			return;
		}

		// Skip on cron, REST, AJAX, and admin requests to avoid impacting TTFB.
		if ( wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || is_admin() ) {
			return;
		}

		$settings = get_option( 'flex_cs_settings', array() );
		if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
			return;
		}

		// Only run once every 5 minutes as a fallback for unreliable cron.
		$last_run = (int) get_option( $this->runtime_lock_option, 0 );
		if ( $last_run > 0 && ( time() - $last_run ) < 300 ) {
			return;
		}

		update_option( $this->runtime_lock_option, time(), false );
		$this->process_due_schedules();
	}
}
