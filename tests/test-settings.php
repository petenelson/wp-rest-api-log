<?php
/**
 * Class WP_REST_API_Log_Test_Settings
 *
 * @package wp-rest-api-log
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Settings extends WP_UnitTestCase {

	/**
	 * Test that general has default settings
	 */
	public function test_default_general_settings() {

		$settings = WP_REST_API_Log_Settings_General::get_default_settings();
		$this->assertNotEmpty( $settings );
		$this->assertNotEmpty( $settings['logging-enabled'], 'logging-enabled is empty' );
	}

	/**
	 * Test that routes have default settings
	 */
	public function test_default_routes_settings() {

		$settings = WP_REST_API_Log_Settings_Routes::get_default_settings();
		$this->assertNotEmpty( $settings );
		$this->assertNotEmpty( $settings['ignore-core-oembed'], 'ignore-core-oembed is empty' );
	}

	/**
	 * Test that headers have default settings
	 */
	public function test_default_headers_settings() {

		$settings = WP_REST_API_Log_Settings_Headers::get_default_settings();
		$this->assertNotEmpty( $settings );
		$this->assertNotEmpty( $settings['redacted-request-headers'], 'redacted-request-headers is empty' );
		$this->assertNotEmpty( $settings['redacted-response-headers'], 'redacted-response-headers is empty' );
	}
}
