<?php
/**
 * Class WP_REST_API_Log_Test_Settings
 *
 * @package 
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Settings extends WP_UnitTestCase {

	/**
	 * Test that general has default settings
	 */
	function test_default_general_settings() {

		$settings = WP_REST_API_Log_Settings_General::get_default_settings();
		$this->assertNotEmpty( $settings );
		$this->assertNotEmpty( $settings['logging-enabled'], 'logging-enabled is empty' );

	}

	/**
	 * Test that routes have default settings
	 */
	function test_default_routes_settings() {

		$settings = WP_REST_API_Log_Settings_Routes::get_default_settings();
		$this->assertNotEmpty( $settings );
		$this->assertNotEmpty( $settings['ignore-core-oembed'], 'ignore-core-oembed is empty' );

	}

}

