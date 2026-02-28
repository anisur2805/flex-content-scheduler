<?php

namespace Anisur\ContentScheduler\Database;

class ScheduleTable {
    public function create_table(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            expiry_date DATETIME NOT NULL,
            expiry_action ENUM('unpublish','delete','redirect','change_status') NOT NULL DEFAULT 'unpublish',
            redirect_url TEXT DEFAULT NULL,
            new_status VARCHAR(20) DEFAULT NULL,
            is_processed TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY expiry_date (expiry_date),
            KEY is_processed (is_processed)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'content_schedules';
    }

    public function drop_table(): void {
        global $wpdb;

        $table_name = self::get_table_name();
        $safe_table_name = preg_replace( '/[^a-zA-Z0-9_]/', '', $table_name );
        if ( ! empty( $safe_table_name ) ) {
            $wpdb->query( "DROP TABLE IF EXISTS `{$safe_table_name}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
    }
}
