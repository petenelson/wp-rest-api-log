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

	public function test_switch_custom_tables() {
		global $wpdb;

		// $a = $wpdb->set_prefix( 'hello_world' );
		// var_dump( $a );
		// var_dump( $wpdb->prefix );
		// die();

		$default_prefix = $wpdb->prefix;
		$custom_prefix = $default_prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

		// Make sure custom tables are turned off.
		$this->disable_custom_tables();

		// Try switching to custom tables, it should not switch.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$this->assertSame( $default_prefix, $wpdb->prefix );

		// Turn on custom tables.
		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		// Switch to custom tables.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		// Verify wpdb is using the custom prefix.
		$this->assertSame( $custom_prefix, $wpdb->prefix );

		// Switch back to default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();

		// Verify wpdb is using the default prefix.
		$this->assertSame( $default_prefix, $wpdb->prefix );
	}

}
