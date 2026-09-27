<?php
/**
 * Class WP_REST_API_Log_Test_Object_Cache_Server
 *
 * @package wp-rest-api-log
 */

/**
 * Confirms the suite is really using a persistent object cache when one is
 * configured, since the Memcached drop-in fails quietly when it can't reach
 * its server.
 */
class WP_REST_API_Log_Test_Object_Cache_Server extends WP_UnitTestCase {

	/**
	 * Tests that values reach the Memcached server, not just the drop-in's
	 * in-memory copy.
	 *
	 * @return void
	 */
	public function test_memcached_server_is_used() {
		if ( empty( getenv( 'WP_TESTS_MEMCACHED_SERVER' ) ) ) {
			$this->markTestSkipped( 'WP_TESTS_MEMCACHED_SERVER is not set.' );
		}

		$this->assertTrue( wp_using_ext_object_cache(), 'The object cache drop-in is not loaded.' );

		$value = wp_generate_password( 12, false );
		$this->assertTrue( wp_cache_set( 'server-check', $value, 'wp-rest-api-log-tests' ) );

		// $force skips the drop-in's in-memory copy and reads from the server.
		$this->assertSame( $value, wp_cache_get( 'server-check', 'wp-rest-api-log-tests', true ) );
	}
}
