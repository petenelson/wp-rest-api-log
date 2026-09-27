<?php
/**
 * Class WP_REST_API_Log_Test_Object_Cache
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the object cache wrapper used while switched to the custom tables.
 */
class WP_REST_API_Log_Test_Object_Cache extends WP_UnitTestCase {

	/**
	 * Tests which cache groups are renamed.
	 *
	 * @return void
	 */
	public function test_map_group() {
		$prefix = WP_REST_API_Log_Object_Cache::GROUP_PREFIX;

		foreach ( array( 'posts', 'post_meta', 'terms', 'term_meta', 'post-queries', 'term-queries', 'counts', 'post_parent', 'timeinfo', 'category_relationships', 'wp-rest-api-log-method_relationships' ) as $group ) {
			$this->assertSame( $prefix . $group, WP_REST_API_Log_Object_Cache::map_group( $group ), $group );
		}

		foreach ( array( 'options', 'users', 'user_meta', 'site-options', 'default', 'transient' ) as $group ) {
			$this->assertSame( $group, WP_REST_API_Log_Object_Cache::map_group( $group ), $group );
		}

		// Empty and non-string groups pass through unchanged.
		$this->assertSame( '', WP_REST_API_Log_Object_Cache::map_group( '' ) );
		$this->assertNull( WP_REST_API_Log_Object_Cache::map_group( null ) );

		// Only the posts and terms last_changed entries are isolated.
		$this->assertSame( $prefix . 'last_changed', WP_REST_API_Log_Object_Cache::map_group( 'last_changed', 'posts' ) );
		$this->assertSame( $prefix . 'last_changed', WP_REST_API_Log_Object_Cache::map_group( 'last_changed', 'terms' ) );
		$this->assertSame( 'last_changed', WP_REST_API_Log_Object_Cache::map_group( 'last_changed', 'users' ) );
		$this->assertSame( 'last_changed', WP_REST_API_Log_Object_Cache::map_group( 'last_changed' ) );
	}

	/**
	 * Tests that the isolated groups can be filtered.
	 *
	 * @return void
	 */
	public function test_isolated_groups_filter() {
		add_filter(
			'wp-rest-api-log-isolated-cache-groups',
			static function ( $groups ) {
				$groups[] = 'my-plugin-posts';
				return $groups;
			}
		);

		$this->assertTrue( WP_REST_API_Log_Object_Cache::is_isolated_group( 'my-plugin-posts' ) );
		$this->assertFalse( WP_REST_API_Log_Object_Cache::is_isolated_group( 'options' ) );
	}

	/**
	 * Tests that cache calls are forwarded with renamed groups.
	 *
	 * @return void
	 */
	public function test_forwards_to_wrapped_cache() {
		global $wp_object_cache;

		$prefix  = WP_REST_API_Log_Object_Cache::GROUP_PREFIX;
		$wrapper = WP_REST_API_Log_Object_Cache::instance()->wrap( $wp_object_cache );

		$this->assertTrue( $wrapper->set( 'key', 'log', 'posts' ) );
		$this->assertTrue( $wrapper->set( 'key', 'shared', 'options' ) );

		// Stored under the renamed group in the real cache.
		$this->assertSame( 'log', wp_cache_get( 'key', $prefix . 'posts' ) );
		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'shared', wp_cache_get( 'key', 'options' ) );

		// get() reports whether the key was found.
		$found = null;
		$this->assertSame( 'log', $wrapper->get( 'key', 'posts', false, $found ) );
		$this->assertTrue( $found );
		$this->assertFalse( $wrapper->get( 'missing', 'posts', false, $found ) );
		$this->assertFalse( $found );

		// Multiple-key and counter methods are renamed too.
		$wrapper->set_multiple( array( 'a' => 1 ), 'terms' );
		$this->assertSame( array( 'a' => 1 ), wp_cache_get_multiple( array( 'a' ), $prefix . 'terms' ) );
		$wrapper->incr( 'a', 1, 'terms' );
		$this->assertSame( 2, wp_cache_get( 'a', $prefix . 'terms' ) );

		$this->assertTrue( $wrapper->delete( 'key', 'posts' ) );
		$this->assertFalse( wp_cache_get( 'key', $prefix . 'posts' ) );

		// Only the posts entry of last_changed is renamed.
		$wrapper->set( 'posts', 'log-time', 'last_changed' );
		$wrapper->set( 'users', 'user-time', 'last_changed' );
		$this->assertSame( 'log-time', wp_cache_get( 'posts', $prefix . 'last_changed' ) );
		$this->assertSame( 'user-time', wp_cache_get( 'users', 'last_changed' ) );

		// Properties and unknown methods reach the wrapped cache.
		$this->assertSame( $wp_object_cache, $wrapper->unwrap() );
		$this->assertTrue( isset( $wrapper->global_groups ) || ! isset( $wp_object_cache->global_groups ) );
	}
}
