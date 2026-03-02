<?php
/**
 * Main plugin bootstrap class.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler;

use Anisur\ContentScheduler\Admin\AdminMenu;
use Anisur\ContentScheduler\Admin\MetaBox;
use Anisur\ContentScheduler\Api\ScheduleRestController;
use Anisur\ContentScheduler\Database\MigrationManager;
use Anisur\ContentScheduler\Integrations\UncannyAutomator\AutomatorIntegration;
use Anisur\ContentScheduler\Scheduler\CronManager;
use Anisur\ContentScheduler\Scheduler\ExpiryActions;
use Anisur\ContentScheduler\Scheduler\ScheduleManager;

/**
 * Class Plugin
 *
 * Orchestrates plugin initialization: loads dependencies, registers hooks, and boots integrations.
 *
 * @since 1.0.0
 */
class Plugin {
	/**
	 * Hook loader instance.
	 *
	 * @since 1.0.0
	 * @var Loader
	 */
	private Loader $loader;

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_slug = 'flex-content-scheduler';

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $version = FLEX_CS_VERSION;

	/**
	 * Schedule manager instance.
	 *
	 * @since 1.0.0
	 * @var ScheduleManager
	 */
	private ScheduleManager $schedule_manager;

	/**
	 * Expiry actions handler.
	 *
	 * @since 1.0.0
	 * @var ExpiryActions
	 */
	private ExpiryActions $expiry_actions;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->load_dependencies();
		$this->run_migrations();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_cron_hooks();
		$this->define_api_hooks();
		$this->load_integrations();
		$this->define_public_hooks();
	}

	/**
	 * Instantiate core service objects.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		$this->schedule_manager = new ScheduleManager();
		$this->expiry_actions   = new ExpiryActions();
	}

	/**
	 * Run database migrations for schema upgrades.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function run_migrations(): void {
		$migrations = new MigrationManager();
		$migrations->migrate();
	}

	/**
	 * Register textdomain loading hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function set_locale(): void {
		$this->loader->add_action( 'init', $this, 'load_textdomain' );
	}

	/**
	 * Load plugin textdomain for translations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'flex-content-scheduler', false, dirname( plugin_basename( FLEX_CS_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Register admin-side hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		$metabox    = new MetaBox( $this->schedule_manager );
		$admin_menu = new AdminMenu( $this->schedule_manager );

		$metabox->register_hooks( $this->loader );
		$admin_menu->register_hooks( $this->loader );
	}

	/**
	 * Register cron-related hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function define_cron_hooks(): void {
		$cron = new CronManager( $this->schedule_manager, $this->expiry_actions );
		$cron->register_hooks( $this->loader );
	}

	/**
	 * Register REST API hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function define_api_hooks(): void {
		$controller = new ScheduleRestController( $this->schedule_manager );
		$this->loader->add_action( 'rest_api_init', $controller, 'register_routes' );
	}

	/**
	 * Load third-party integrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function load_integrations(): void {
		if ( ! class_exists( 'Uncanny_Automator\\Automator_Load' ) ) {
			return;
		}

		$integration = new AutomatorIntegration( $this->expiry_actions );
		$integration->register();
	}

	/**
	 * Register frontend-facing hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function define_public_hooks(): void {
		$this->loader->add_action( 'template_redirect', $this->expiry_actions, 'handle_template_redirect' );
	}

	/**
	 * Execute all registered hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}
}
