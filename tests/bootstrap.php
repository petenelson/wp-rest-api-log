<?php
/**
 * PHPUnit bootstrap for the plugin's test suite.
 *
 * @package wp-rest-api-log
 */

/**
 * Loads WordPress' test suite and the plugin under test.
 */
class WP_REST_API_Log_Tests_Bootstrap {

	/**
	 * Absolute path to the plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_root = '';

	/**
	 * Sets up the unit test environment.
	 *
	 * @throws Exception When WP_DEVELOP_DIR is not set or Composer has not been run.
	 * @return void
	 */
	public function bootstrap() {

		// Run this in your shell to point to wherever you cloned WordPress:
		// git clone git@github.com:WordPress/wordpress-develop.git
		// Example: export WP_DEVELOP_DIR="/Users/petenelson/projects/wordpress/wordpress-develop/".
		$wp_develop_dir = getenv( 'WP_DEVELOP_DIR' );

		if ( empty( $wp_develop_dir ) ) {
			throw new Exception(
				'ERROR' . PHP_EOL . PHP_EOL .
				'You must define the WP_DEVELOP_DIR environment variable.' . PHP_EOL
			);
		}

		// Load the Composer autoloader.
		$this->plugin_root = dirname( __DIR__ );
		if ( ! file_exists( $this->plugin_root . '/vendor/autoload.php' ) ) {
			throw new Exception(
				'ERROR' . PHP_EOL . PHP_EOL .
				'You must use Composer to install the test suite\'s dependencies.' . PHP_EOL
			);
		}
		$autoloader = require_once $this->plugin_root . '/vendor/autoload.php';

		// Give access to tests_add_filter() function.
		require_once $wp_develop_dir . '/tests/phpunit/includes/functions.php';

		tests_add_filter( 'muplugins_loaded', array( $this, 'manually_load_plugin' ) );

		// Start up the WP testing environment.
		require $wp_develop_dir . '/tests/phpunit/includes/bootstrap.php';
	}

	/**
	 * Manually loads the plugin being tested.
	 *
	 * @return void
	 */
	public function manually_load_plugin() {
		require $this->plugin_root . '/wp-rest-api-log.php';
	}
}

$wp_rest_api_log_tests = new WP_REST_API_Log_Tests_Bootstrap();
$wp_rest_api_log_tests->bootstrap();
