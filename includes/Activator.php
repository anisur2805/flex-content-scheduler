<?php

namespace Anisur\ContentScheduler;

use Anisur\ContentScheduler\Database\ScheduleTable;

class Activator {
    public static function add_every_minute_schedule( array $schedules ): array {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Every Minute', 'flex-content-scheduler' ),
        );
        return $schedules;
    }

    public static function activate(): void {
        global $wp_version;

        if ( version_compare( PHP_VERSION, '7.4', '<' ) || version_compare( (string) $wp_version, '6.0', '<' ) ) {
            deactivate_plugins( plugin_basename( FCS_PLUGIN_FILE ) );
            wp_die( esc_html__( 'Flex Content Scheduler requires WordPress 6.0+ and PHP 7.4+.', 'flex-content-scheduler' ) );
        }

        $table = new ScheduleTable();
        $table->create_table();

        add_filter( 'cron_schedules', array( __CLASS__, 'add_every_minute_schedule' ) );
        if ( ! wp_next_scheduled( 'fcs_process_schedules' ) ) {
            wp_schedule_event( time(), 'every_minute', 'fcs_process_schedules' );
        }
        remove_filter( 'cron_schedules', array( __CLASS__, 'add_every_minute_schedule' ) );

        flush_rewrite_rules();
        update_option( 'fcs_version', FCS_VERSION );
        if ( false === get_option( 'fcs_settings', false ) ) {
            update_option(
                'fcs_settings',
                array(
                    'default_action'     => 'unpublish',
                    'cron_enabled'       => true,
                    'notification_email' => '',
                )
            );
        }
    }
}
