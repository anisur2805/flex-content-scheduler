<?php

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public string $code;
        public string $message;
        public array $data;

        public function __construct( string $code = '', string $message = '', array $data = array() ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }
    }
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
    class WP_REST_Controller {}
}

$GLOBALS['fcs_actions_fired'] = array();
$GLOBALS['fcs_stub_post_exists'] = true;
$GLOBALS['fcs_deleted_meta'] = array();

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = '' ): string {
        return $text;
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( int $post_id ) {
        if ( $post_id <= 0 ) {
            return null;
        }
        return ! empty( $GLOBALS['fcs_stub_post_exists'] ) ? (object) array( 'ID' => $post_id ) : null;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( string $hook, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( string $hook, ...$args ): void {
        $GLOBALS['fcs_actions_fired'][] = array(
            'hook' => $hook,
            'args' => $args,
        );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $text ): string {
        return trim( strip_tags( $text ) );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( string $url ): string {
        return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $value ): int {
        return abs( (int) $value );
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( array $args, array $defaults ): array {
        return array_merge( $defaults, $args );
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( string $type = 'mysql', bool $gmt = false ): string {
        return gmdate( 'Y-m-d H:i:s' );
    }
}

if ( ! function_exists( 'wp_update_post' ) ) {
    function wp_update_post( array $postarr, bool $wp_error = false ) {
        return $postarr['ID'] ?? 0;
    }
}

if ( ! function_exists( 'wp_delete_post' ) ) {
    function wp_delete_post( int $post_id, bool $force_delete = false ) {
        return $post_id > 0 ? (object) array( 'ID' => $post_id ) : null;
    }
}

if ( ! function_exists( 'wp_http_validate_url' ) ) {
    function wp_http_validate_url( string $url ): bool {
        return (bool) filter_var( $url, FILTER_VALIDATE_URL );
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
        return $post_id > 0 && '' !== $meta_key;
    }
}

if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( int $post_id, string $meta_key ): bool {
        $GLOBALS['fcs_deleted_meta'][] = array(
            'post_id'  => $post_id,
            'meta_key' => $meta_key,
        );
        return true;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, $default = false ) {
        if ( 'fcs_settings' === $option ) {
            return array(
                'default_action' => 'unpublish',
                'cron_enabled'   => true,
            );
        }

        return $default;
    }
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
    function wp_schedule_single_event( int $timestamp, string $hook ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( int $timestamp, string $recurrence, string $hook ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( string $hook ) {
        return false;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook( string $hook ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
    function wp_doing_cron(): bool {
        return false;
    }
}
