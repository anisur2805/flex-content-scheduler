<?php

namespace Anisur\ContentScheduler;

use Anisur\ContentScheduler\Admin\AdminMenu;
use Anisur\ContentScheduler\Admin\MetaBox;
use Anisur\ContentScheduler\Api\ScheduleRestController;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\AutomatorIntegration;
use Anisur\ContentScheduler\Scheduler\CronManager;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;

class Plugin {
	private Loader $loader;
	private string $plugin_slug = 'flex-content-scheduler';
	private string $version = FCS_VERSION;

	private ScheduleManager $schedule_manager;
	private ExpiryActions $expiry_actions;

	public function __construct() {
		$this->loader = new Loader();
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_cron_hooks();
		$this->define_api_hooks();
		$this->load_integrations();
		$this->define_public_hooks();
	}

	private function load_dependencies(): void {
		$this->schedule_manager = new ScheduleManager();
		$this->expiry_actions   = new ExpiryActions();
	}

	private function set_locale(): void {
		$this->loader->add_action( 'init', $this, 'load_textdomain' );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'flex-content-scheduler', false, dirname( plugin_basename( FCS_PLUGIN_FILE ) ) . '/languages/' );
	}

	private function define_admin_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		$metabox    = new MetaBox( $this->schedule_manager );
		$admin_menu = new AdminMenu( $this->schedule_manager );

		$metabox->register_hooks( $this->loader );
		$admin_menu->register_hooks( $this->loader );
	}

	private function define_cron_hooks(): void {
		$cron = new CronManager( $this->schedule_manager, $this->expiry_actions );
		$cron->register_hooks( $this->loader );
	}

	private function define_api_hooks(): void {
		$controller = new ScheduleRestController( $this->schedule_manager );
		$this->loader->add_action( 'rest_api_init', $controller, 'register_routes' );
	}

	private function load_integrations(): void {
		if ( ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			return;
		}

		$integration = new AutomatorIntegration( $this->expiry_actions );
		$integration->register();
	}

	private function define_public_hooks(): void {
		$this->loader->add_action( 'template_redirect', $this->expiry_actions, 'handle_template_redirect' );
	}

	public function run(): void {
		$this->loader->run();
	}
}
