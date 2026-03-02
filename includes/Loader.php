<?php
/**
 * Hook loader for the plugin.
 *
 * @package Flex_Content_Scheduler
 * @since   1.0.0
 */

namespace Anisur\ContentScheduler;

/**
 * Class Loader
 *
 * Registers and executes all actions and filters for the plugin.
 *
 * @since 1.0.0
 */
class Loader {
	/**
	 * Registered actions.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $actions = array();

	/**
	 * Registered filters.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $filters = array();

	/**
	 * Register an action hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Component instance.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 * @return void
	 */
	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Register a filter hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Component instance.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 * @return void
	 */
	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Execute all registered hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
