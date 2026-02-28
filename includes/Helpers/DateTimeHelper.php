<?php

namespace Anisur\ContentScheduler\Helpers;

class DateTimeHelper {
    public static function is_past( string $utc_datetime ): bool {
        $now = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
        $dt  = new \DateTime( $utc_datetime, new \DateTimeZone( 'UTC' ) );

        return $dt <= $now;
    }

    public static function format_for_display( string $utc_datetime, string $timezone = '' ): string {
        $tz = $timezone ? new \DateTimeZone( $timezone ) : wp_timezone();
        $dt = new \DateTime( $utc_datetime, new \DateTimeZone( 'UTC' ) );
        $dt->setTimezone( $tz );

        return $dt->format( 'Y-m-d H:i:s T' );
    }

    public static function to_utc( string $datetime, string $timezone ): string {
        $dt = new \DateTime( $datetime, new \DateTimeZone( $timezone ) );
        $dt->setTimezone( new \DateTimeZone( 'UTC' ) );

        return $dt->format( 'Y-m-d H:i:s' );
    }
}
