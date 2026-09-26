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

		// Shared test helpers.
		require_once __DIR__ . '/includes/trait-wp-rest-api-log-test-hooks.php';

		// Give access to tests_add_filter() function.
		require_once $wp_develop_dir . '/tests/phpunit/includes/functions.php';

		tests_add_filter( 'muplugins_loaded', array( $this, 'manually_load_plugin' ) );

		// Running from wordpress-develop's src/ turns SCRIPT_DEBUG on. Before
		// WordPress 7.0, that makes WP_Scripts include development asset files
		// that only exist after a core build, which a plain clone does not
		// have. From 7.0 the opposite applies: the unminified assets are the
		// ones in the clone, so SCRIPT_DEBUG is left on there.
		if ( ! defined( 'SCRIPT_DEBUG' ) && version_compare( $this->core_version( $wp_develop_dir ), '7.0-alpha', '<' ) ) {
			define( 'SCRIPT_DEBUG', false );
		}

		// Start up the WP testing environment.
		require $wp_develop_dir . '/tests/phpunit/includes/bootstrap.php';
	}

	/**
	 * Reads the WordPress version from a wordpress-develop checkout without
	 * loading WordPress.
	 *
	 * @param  string $wp_develop_dir Path to the wordpress-develop checkout.
	 * @return string WordPress version, or an empty string if it can't be read.
	 */
	protected function core_version( $wp_develop_dir ) {
		$wp_version = '';

		// Only sets version variables, which stay local to this method.
		include rtrim( $wp_develop_dir, '/' ) . '/src/wp-includes/version.php';

		return $wp_version;
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
