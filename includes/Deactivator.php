<?php

namespace Anisur\ContentScheduler;

class Deactivator {
    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'fcs_process_schedules' );
        flush_rewrite_rules();
    }
}
