<?php
/**
 * Trait WP_REST_API_Log_Test_Hooks
 *
 * @package wp-rest-api-log
 */

/**
 * Helper for testing the methods that register a class's hooks.
 */
trait WP_REST_API_Log_Test_Hooks {

	/**
	 * Asserts that a callback registers the given hooks.
	 *
	 * The plugin registers its hooks while the test suite boots, so each hook
	 * is removed first to show that the callback adds it again. The test
	 * suite restores all hooks after each test.
	 *
	 * @param callable $register Callback that registers the hooks.
	 * @param array    $hooks    List of hooks, each a list of hook name,
	 *                           callback and priority.
	 * @return void
	 */
	protected function assert_registers_hooks( $register, $hooks ) {

		foreach ( $hooks as $hook ) {
			remove_filter( $hook[0], $hook[1], $hook[2] );
			$this->assertFalse( has_filter( $hook[0], $hook[1] ), $hook[0] );
		}

		call_user_func( $register );

		foreach ( $hooks as $hook ) {
			$this->assertSame( $hook[2], has_filter( $hook[0], $hook[1] ), $hook[0] );
		}
	}
}
