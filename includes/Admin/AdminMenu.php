<?php

namespace Anisur\ContentScheduler\Admin;

use Anisur\ContentScheduler\Loader;
use Anisur\ContentScheduler\Helpers\PostTypeHelper;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;

class AdminMenu {
	private ScheduleManager $schedule_manager;

	public function __construct( ScheduleManager $schedule_manager ) {
		$this->schedule_manager = $schedule_manager;
	}

	public function register_hooks( Loader $loader ): void {
		$loader->add_action( 'admin_menu', $this, 'add_menu_page' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );
	}

	public function add_menu_page(): void {
		add_management_page(
			__( 'Content Schedules', 'flex-content-scheduler' ),
			__( 'Content Schedules', 'flex-content-scheduler' ),
			'manage_options',
			'flex-cs-schedules',
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		echo '<div class="wrap"><h1>' . esc_html__( 'Flex Content Scheduler', 'flex-content-scheduler' ) . '</h1><div id="flex-cs-admin-root"></div></div>';
	}

	public function enqueue_scripts( string $hook ): void {
		if ( 'tools_page_flex-cs-schedules' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'flex-cs-admin',
			FLEX_CS_PLUGIN_URL . 'assets/dist/admin.js',
			array( 'wp-element', 'wp-api-fetch', 'wp-i18n', 'wp-components' ),
			FLEX_CS_VERSION,
			true
		);

		wp_enqueue_style(
			'flex-cs-admin',
			FLEX_CS_PLUGIN_URL . 'assets/dist/admin.css',
			array(),
			FLEX_CS_VERSION
		);

		wp_localize_script(
			'flex-cs-admin',
			'flexCSAdmin',
			array(
				'restUrl'   => esc_url_raw( rest_url( 'flex-cs/v1' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'postTypes' => PostTypeHelper::get_public_post_types(),
				'settings'  => get_option(
					'flex_cs_settings',
					array(
						'default_action'     => 'unpublish',
						'cron_enabled'       => true,
						'notification_email' => '',
					)
				),
			)
		);
	}
}
