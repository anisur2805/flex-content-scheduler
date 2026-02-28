<?php

namespace Anisur\ContentScheduler\Scheduler;

use Anisur\ContentScheduler\Loader;

class CronManager {
    private ScheduleManager $schedule_manager;
    private ExpiryActions $expiry_actions;
    private string $runtime_lock_option = 'fcs_last_runtime_process';

    public function __construct( ScheduleManager $schedule_manager, ExpiryActions $expiry_actions ) {
        $this->schedule_manager = $schedule_manager;
        $this->expiry_actions   = $expiry_actions;
    }

    public function register_hooks( Loader $loader ): void {
        $loader->add_filter( 'cron_schedules', $this, 'register_custom_interval' );
        $loader->add_action( 'fcs_process_schedules', $this, 'process_due_schedules', 10, 0 );
        $loader->add_action( 'init', $this, 'ensure_cron_event_scheduled', 20, 0 );
        $loader->add_action( 'init', $this, 'maybe_process_due_schedules_runtime', 30, 0 );
    }

    public function register_custom_interval( array $schedules ): array {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Every Minute', 'flex-content-scheduler' ),
        );

        return $schedules;
    }

    public function process_due_schedules(): void {
        $settings = get_option( 'fcs_settings', array() );
        if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
            do_action( 'fcs_cron_processed', 0 );
            return;
        }

        $due_schedules   = $this->schedule_manager->get_due_schedules();
        $processed_count = 0;

        foreach ( $due_schedules as $schedule ) {
            $result = $this->expiry_actions->process( $schedule );

            if ( $result ) {
                $this->schedule_manager->mark_processed( (int) $schedule->id );
                $processed_count++;
                continue;
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( sprintf( '[FCS] Failed processing schedule ID %d', (int) $schedule->id ) );
            }
        }

        do_action( 'fcs_cron_processed', $processed_count );
    }

    public function ensure_cron_event_scheduled(): void {
        $settings = get_option( 'fcs_settings', array() );
        if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
            wp_clear_scheduled_hook( 'fcs_process_schedules' );
            return;
        }

        if ( ! wp_next_scheduled( 'fcs_process_schedules' ) ) {
            wp_schedule_event( time() + 60, 'every_minute', 'fcs_process_schedules' );
        }
    }

    public function maybe_process_due_schedules_runtime(): void {
        if ( wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        $settings = get_option( 'fcs_settings', array() );
        if ( isset( $settings['cron_enabled'] ) && ! $settings['cron_enabled'] ) {
            return;
        }

        $last_run = (int) get_option( $this->runtime_lock_option, 0 );
        if ( $last_run > 0 && ( time() - $last_run ) < 60 ) {
            return;
        }

        update_option( $this->runtime_lock_option, time(), false );
        $this->process_due_schedules();
    }
}
