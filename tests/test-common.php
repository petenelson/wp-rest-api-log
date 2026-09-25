<?php
/**
 * Class WP_REST_API_Log_Test_Common
 *
 * @package wp-rest-api-log
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Common extends WP_UnitTestCase {

	/**
	 * Make sure valid methods returns results
	 */
	public function test_valid_methods() {
		$valid_methods = WP_REST_API_Log_Common::valid_methods();
		$this->assertTrue( ! empty( $valid_methods ) );
		$this->assertContains( 'GET', $valid_methods );
		$this->assertContains( 'OPTIONS', $valid_methods );
	}

	/**
	 * Test that GET and OPTIONS are valid methods
	 */
	public function test_valid_method() {
		$valid_methods = WP_REST_API_Log_Common::valid_methods();
		$this->assertTrue( WP_REST_API_Log_Common::is_valid_method( 'GET' ) );
		$this->assertTrue( WP_REST_API_Log_Common::is_valid_method( 'OPTIONS' ) );
	}
}
