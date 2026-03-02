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

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
	class WP_REST_Controller {}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private string $method;
		private array $params;
		private array $headers;
		private array $json_params;

		public function __construct( string $method = 'GET', array $params = array(), array $json_params = array(), array $headers = array() ) {
			$this->method     = strtoupper( $method );
			$this->params     = $params;
			$this->json_params = $json_params;
			$this->headers    = array_change_key_case( $headers, CASE_LOWER );
		}

		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		public function get_json_params(): array {
			return $this->json_params;
		}

		public function set_json_params( array $json_params ): void {
			$this->json_params = $json_params;
		}

		public function get_method(): string {
			return $this->method;
		}

		public function get_header( string $name ): string {
			$key = strtolower( $name );
			return isset( $this->headers[ $key ] ) ? (string) $this->headers[ $key ] : '';
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		private $data;
		private int $status;
		private array $headers = array();

		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function header( string $key, string $value ): void {
			$this->headers[ $key ] = $value;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}

		public function get_headers(): array {
			return $this->headers;
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public int $ID = 0;
	}
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

$GLOBALS['flex_cs_actions_fired']    = array();
$GLOBALS['flex_cs_stub_post_exists'] = true;
$GLOBALS['flex_cs_deleted_meta']     = array();
$GLOBALS['flex_cs_filters']          = array();
$GLOBALS['flex_cs_actions']          = array();
$GLOBALS['flex_cs_options']          = array(
	'flex_cs_settings' => array(
		'default_action'         => 'unpublish',
		'cron_enabled'           => true,
		'notification_email'     => '',
		'allowed_redirect_hosts' => array(),
	),
);
$GLOBALS['flex_cs_transients']       = array();
$GLOBALS['flex_cs_cache']            = array();
$GLOBALS['flex_cs_redirect_to']      = '';
$GLOBALS['flex_cs_redirect_status']  = 0;
$GLOBALS['flex_cs_current_user_id']  = 1;
$GLOBALS['flex_cs_user_caps']        = array(
	'edit_posts' => true,
	'manage_options' => true,
	'edit_post' => true,
);
$GLOBALS['flex_cs_registered_routes'] = array();
$GLOBALS['flex_cs_enqueued_scripts']  = array();
$GLOBALS['flex_cs_enqueued_styles']   = array();
$GLOBALS['flex_cs_localized_scripts'] = array();
$GLOBALS['flex_cs_added_meta_boxes']  = array();
$GLOBALS['flex_cs_management_pages']  = array();
$GLOBALS['flex_cs_dbdelta_sql']       = '';
$GLOBALS['flex_cs_wpdie_called']      = false;
$GLOBALS['flex_cs_deactivated_plugin'] = '';
$GLOBALS['flex_cs_next_scheduled']    = false;
$GLOBALS['flex_cs_scheduled_events']  = array();
$GLOBALS['flex_cs_cleared_hooks']     = array();
$GLOBALS['flex_cs_rewrite_flushed']   = 0;
$GLOBALS['flex_cs_is_admin']          = false;
$GLOBALS['flex_cs_is_singular']       = true;
$GLOBALS['flex_cs_queried_object_id'] = 0;
$GLOBALS['flex_cs_post_meta']         = array();
$GLOBALS['flex_cs_sent_emails']       = array();

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = '' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = '' ): string {
		return $text;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['flex_cs_filters'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( string $hook, $callback ): void {
		if ( empty( $GLOBALS['flex_cs_filters'][ $hook ] ) ) {
			return;
		}

		foreach ( $GLOBALS['flex_cs_filters'][ $hook ] as $idx => $registered_callback ) {
			if ( $registered_callback === $callback ) {
				unset( $GLOBALS['flex_cs_filters'][ $hook ][ $idx ] );
			}
		}
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		if ( empty( $GLOBALS['flex_cs_filters'][ $hook ] ) ) {
			return $value;
		}

		$filtered = $value;
		foreach ( $GLOBALS['flex_cs_filters'][ $hook ] as $callback ) {
			$filtered = call_user_func_array( $callback, array_merge( array( $filtered ), $args ) );
		}

		return $filtered;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$GLOBALS['flex_cs_actions'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		$GLOBALS['flex_cs_actions_fired'][] = array(
			'hook' => $hook,
			'args' => $args,
		);

		if ( empty( $GLOBALS['flex_cs_actions'][ $hook ] ) ) {
			return;
		}

		foreach ( $GLOBALS['flex_cs_actions'][ $hook ] as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( string $namespace, string $route, array $args ): void {
		$GLOBALS['flex_cs_registered_routes'][] = array(
			'namespace' => $namespace,
			'route'     => $route,
			'args'      => $args,
		);
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $data ) {
		if ( $data instanceof WP_REST_Response ) {
			return $data;
		}

		return new WP_REST_Response( $data, 200 );
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

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( string $email ): string {
		return filter_var( $email, FILTER_SANITIZE_EMAIL ) ?: '';
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

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
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

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
		$GLOBALS['flex_cs_post_meta'][ $post_id ][ $meta_key ] = $meta_value;
		return $post_id > 0 && '' !== $meta_key;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( int $post_id, string $meta_key ): bool {
		$GLOBALS['flex_cs_deleted_meta'][] = array(
			'post_id'  => $post_id,
			'meta_key' => $meta_key,
		);
		unset( $GLOBALS['flex_cs_post_meta'][ $post_id ][ $meta_key ] );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
		$value = $GLOBALS['flex_cs_post_meta'][ $post_id ][ $meta_key ] ?? '';
		return $single ? $value : array( $value );
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( int $post_id ) {
		if ( $post_id <= 0 ) {
			return null;
		}
		return ! empty( $GLOBALS['flex_cs_stub_post_exists'] ) ? (object) array( 'ID' => $post_id ) : null;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		if ( array_key_exists( $option, $GLOBALS['flex_cs_options'] ) ) {
			return $GLOBALS['flex_cs_options'][ $option ];
		}

		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value, bool $autoload = true ): bool {
		$GLOBALS['flex_cs_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		return $GLOBALS['flex_cs_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		$GLOBALS['flex_cs_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( string $key, string $group = '' ) {
		$group_key = $group . ':' . $key;
		return $GLOBALS['flex_cs_cache'][ $group_key ] ?? false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( string $key, $value, string $group = '' ): bool {
		$group_key = $group . ':' . $key;
		$GLOBALS['flex_cs_cache'][ $group_key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( string $key, string $group = '' ): bool {
		$group_key = $group . ':' . $key;
		unset( $GLOBALS['flex_cs_cache'][ $group_key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $capability, ...$args ): bool {
		if ( 'edit_post' === $capability ) {
			return ! empty( $GLOBALS['flex_cs_user_caps']['edit_post'] );
		}

		return ! empty( $GLOBALS['flex_cs_user_caps'][ $capability ] );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return (int) $GLOBALS['flex_cs_current_user_id'];
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( string $nonce, string $action ): bool {
		return 'valid-nonce' === $nonce || 'wp_rest_nonce' === $nonce;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string {
		return 'wp_rest_nonce';
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( int $timestamp, string $hook ): bool {
		$GLOBALS['flex_cs_scheduled_events'][] = array( 'timestamp' => $timestamp, 'hook' => $hook, 'single' => true );
		return true;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( int $timestamp, string $recurrence, string $hook ): bool {
		$GLOBALS['flex_cs_scheduled_events'][] = array( 'timestamp' => $timestamp, 'hook' => $hook, 'recurrence' => $recurrence, 'single' => false );
		return true;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( string $hook ) {
		return $GLOBALS['flex_cs_next_scheduled'];
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( string $hook ): bool {
		$GLOBALS['flex_cs_cleared_hooks'][] = $hook;
		return true;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron(): bool {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return (bool) $GLOBALS['flex_cs_is_admin'];
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular(): bool {
		return (bool) $GLOBALS['flex_cs_is_singular'];
	}
}

if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id(): int {
		return (int) $GLOBALS['flex_cs_queried_object_id'];
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( string $location, int $status = 302, string $x_redirect_by = '' ): bool {
		$GLOBALS['flex_cs_redirect_to']     = $location;
		$GLOBALS['flex_cs_redirect_status'] = $status;
		return true;
	}
}

if ( ! function_exists( 'add_management_page' ) ) {
	function add_management_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback ) {
		$GLOBALS['flex_cs_management_pages'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );
		return 'tools_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = false, bool $in_footer = false ): void {
		$GLOBALS['flex_cs_enqueued_scripts'][] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( string $handle, string $src = '', array $deps = array(), $ver = false, string $media = 'all' ): void {
		$GLOBALS['flex_cs_enqueued_styles'][] = compact( 'handle', 'src', 'deps', 'ver', 'media' );
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $object_name, array $l10n ): bool {
		$GLOBALS['flex_cs_localized_scripts'][] = compact( 'handle', 'object_name', 'l10n' );
		return true;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( string $path = '' ): string {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( array $args = array(), string $output = 'names' ) {
		if ( 'objects' === $output ) {
			return array(
				'post' => (object) array( 'name' => 'post', 'label' => 'Posts' ),
				'page' => (object) array( 'name' => 'page', 'label' => 'Pages' ),
			);
		}

		return array( 'post', 'page' );
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( string $id, string $title, $callback, string $screen, string $context = 'advanced', string $priority = 'default' ): void {
		$GLOBALS['flex_cs_added_meta_boxes'][] = compact( 'id', 'title', 'callback', 'screen', 'context', 'priority' );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, string $name = '_wpnonce', bool $referer = true, bool $display = true ): string {
		return '';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( string $file ): string {
		return basename( $file );
	}
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
	function deactivate_plugins( string $plugin ): void {
		$GLOBALS['flex_cs_deactivated_plugin'] = $plugin;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( string $message = '' ): void {
		$GLOBALS['flex_cs_wpdie_called'] = true;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules(): void {
		$GLOBALS['flex_cs_rewrite_flushed']++;
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( string $sql ): void {
		$GLOBALS['flex_cs_dbdelta_sql'] = $sql;
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( string $domain, bool $deprecated = false, string $plugin_rel_path = '' ): bool {
		return true;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( int $post_id ): string {
		return 'Post #' . $post_id;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( int $post_id ): string {
		return 'post';
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = array(), $attachments = array() ): bool {
		$GLOBALS['flex_cs_sent_emails'][] = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachments,
		);

		return true;
	}
}
