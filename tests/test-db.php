<?php
/**
 * Class WP_REST_API_Log_Test_DB
 *
 * @package 
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_DB extends WP_UnitTestCase {

	private $_registered = false;

	public function enable_custom_tables() {
		update_option( 'wp-rest-api-log-settings-advanced', [
			'use-custom-tables' => '1',
		] );
	}

	public function disable_custom_tables() {
		update_option( 'wp-rest-api-log-settings-advanced', [
			'use-custom-tables' => '0',
		] );
	}

	public function register_settings() {
		if ( ! $this->_registered ) {
			WP_REST_API_Log_Settings_Advanced::register_advanced_settings();
			$this->_registered = true;
		}
	}

	public function setUp() {
		$this->register_settings();
	}

	public function test_get_custom_table_prefix() {
		$this->assertSame( 'rest_api_log_', WP_REST_API_Log_DB::get_custom_table_prefix() );
	}

	public function test_use_custom_tables() {

		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );

		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		$this->disable_custom_tables();
		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );
	}


}
