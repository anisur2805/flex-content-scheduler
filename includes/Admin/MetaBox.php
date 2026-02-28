<?php

namespace Anisur\ContentScheduler\Admin;

use Anisur\ContentScheduler\Loader;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;

class MetaBox {
	private ScheduleManager $schedule_manager;

	public function __construct( ScheduleManager $schedule_manager ) {
		$this->schedule_manager = $schedule_manager;
	}

	public function register_hooks( Loader $loader ): void {
		$loader->add_action( 'add_meta_boxes', $this, 'register_meta_box' );
		$loader->add_action( 'save_post', $this, 'save_meta_box_data' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );
	}

	public function register_meta_box(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'fcs-content-expiry',
				__( 'Content Expiry Schedule', 'flex-content-scheduler' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'fcs_metabox_save', 'fcs_metabox_nonce' );
		echo '<div id="fcs-metabox-root" data-post-id="' . esc_attr( (string) $post->ID ) . '"></div>';
	}

	public function save_meta_box_data( int $post_id ): void {
		if ( ! isset( $_POST['fcs_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fcs_metabox_nonce'] ) ), 'fcs_metabox_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST['fcs_expiry_action'] ) || empty( $_POST['fcs_expiry_date'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['fcs_expiry_action'] ) );
		$date   = sanitize_text_field( wp_unslash( $_POST['fcs_expiry_date'] ) );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date ) ) {
			return;
		}

		$utc_date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
		$data     = array(
			'post_id'       => $post_id,
			'expiry_date'   => $utc_date,
			'expiry_action' => $action,
			'redirect_url'  => isset( $_POST['fcs_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['fcs_redirect_url'] ) ) : '',
			'new_status'    => isset( $_POST['fcs_new_status'] ) ? sanitize_key( wp_unslash( $_POST['fcs_new_status'] ) ) : '',
		);

		$existing = $this->schedule_manager->get_schedule_by_post( $post_id );

		if ( isset( $_POST['fcs_delete_schedule'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['fcs_delete_schedule'] ) ) ) {
			if ( $existing ) {
				$this->schedule_manager->delete_schedule( (int) $existing->id );
			}
			return;
		}

		if ( $existing ) {
			$this->schedule_manager->update_schedule( (int) $existing->id, $data );
			return;
		}

		$this->schedule_manager->create_schedule( $data );
	}

	public function enqueue_scripts( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post;

		wp_enqueue_script(
			'fcs-metabox',
			FLEX_CS_PLUGIN_URL . 'assets/dist/metabox.js',
			array( 'wp-element', 'wp-api-fetch', 'wp-i18n' ),
			FLEX_CS_VERSION,
			true
		);

		wp_enqueue_style(
			'fcs-metabox',
			FLEX_CS_PLUGIN_URL . 'assets/dist/metabox.css',
			array(),
			FLEX_CS_VERSION
		);

		wp_localize_script(
			'fcs-metabox',
			'flexCSMetabox',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'fcs/v1' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'postId'   => isset( $post->ID ) ? (int) $post->ID : 0,
				'schedule' => isset( $post->ID ) ? $this->schedule_manager->get_schedule_by_post( (int) $post->ID ) : null,
			)
		);
	}
}
