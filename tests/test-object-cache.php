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
	 * Tests that the wp_cache_*() functions reach the site's cache with
	 * renamed groups while the wrapper is installed. Runs against whichever
	 * object cache is active, so CI covers the Memcached drop-in too.
	 *
	 * @return void
	 */
	public function test_forwards_to_wrapped_cache() {
		global $wp_object_cache;

		$prefix     = WP_REST_API_Log_Object_Cache::GROUP_PREFIX;
		$site_cache = $wp_object_cache;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Installs the wrapper the way switch_to_custom_tables() does.
		$wp_object_cache = WP_REST_API_Log_Object_Cache::instance()->wrap( $site_cache );

		try {
			$this->assertTrue( wp_cache_set( 'key', 'log', 'posts' ) );
			$this->assertTrue( wp_cache_set( 'key', 'shared', 'options' ) );

			// get() reports whether the key was found.
			$found = null;
			$this->assertSame( 'log', wp_cache_get( 'key', 'posts', false, $found ) );
			$this->assertTrue( $found );
			$this->assertFalse( wp_cache_get( 'missing', 'posts', false, $found ) );
			$this->assertFalse( $found );

			// Multiple-key and counter functions are renamed too.
			$this->assertSame( array( 'a' => true ), wp_cache_set_multiple( array( 'a' => 1 ), 'terms' ) );
			$this->assertSame( array( 'b' => true ), wp_cache_add_multiple( array( 'b' => 2 ), 'terms' ) );
			$this->assertSame(
				array(
					'a' => 1,
					'b' => 2,
				),
				wp_cache_get_multiple( array( 'a', 'b' ), 'terms' )
			);
			$this->assertSame( 2, wp_cache_incr( 'a', 1, 'terms' ) );

			// Only the posts entry of last_changed is renamed.
			wp_cache_set( 'posts', 'log-time', 'last_changed' );
			wp_cache_set( 'users', 'user-time', 'last_changed' );
		} finally {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the site's cache.
			$wp_object_cache = $site_cache;
		}

		// Stored under the renamed groups in the site's cache.
		$this->assertSame( 'log', wp_cache_get( 'key', $prefix . 'posts' ) );
		$this->assertFalse( wp_cache_get( 'key', 'posts' ) );
		$this->assertSame( 'shared', wp_cache_get( 'key', 'options' ) );
		$this->assertSame( 2, wp_cache_get( 'a', $prefix . 'terms' ) );
		$this->assertFalse( wp_cache_get( 'a', 'terms' ) );
		$this->assertFalse( wp_cache_get( 'b', 'terms' ) );
		$this->assertSame( 'log-time', wp_cache_get( 'posts', $prefix . 'last_changed' ) );
		$this->assertSame( 'user-time', wp_cache_get( 'users', 'last_changed' ) );
	}

	/**
	 * Tests the group mapping for drop-in methods that core's cache doesn't
	 * have, such as the camelCase methods in wordpress-develop's Memcached
	 * drop-in, using a stand-in cache that records each call.
	 *
	 * @return void
	 */
	public function test_forwards_drop_in_methods() {
		$prefix  = WP_REST_API_Log_Object_Cache::GROUP_PREFIX;
		$cache   = new WP_REST_API_Log_Test_Recording_Cache();
		$wrapper = WP_REST_API_Log_Object_Cache::instance()->wrap( $cache );

		$wrapper->addMultiple( array( 'a' => 1 ), 'posts' );
		$wrapper->getMultiple( array( 'a' ), 'post_meta', false );
		$wrapper->setMultiple( array( 'a' => 1 ), 'options' );
		$wrapper->deleteMultiple( array( 'a' ), 'terms' );
		$wrapper->getMulti( array( 'a', 'b' ), array( 'posts', 'options' ) );
		$wrapper->setByKey( 'server', 'a', 1, 'term_meta' );
		$wrapper->setByKey( 'server', 'posts', 1, 'last_changed' );
		$wrapper->casByKey( 'token', 'server', 'a', 1, 'category_relationships' );
		$wrapper->someUnknownMethod( 'a', 'posts' );

		$found = null;
		$this->assertSame( 'value', $wrapper->getByKey( 'server', 'a', 'posts', false, $found ) );
		$this->assertTrue( $found );

		$this->assertSame(
			array(
				array( 'addMultiple', array( array( 'a' => 1 ), $prefix . 'posts' ) ),
				array( 'getMultiple', array( array( 'a' ), $prefix . 'post_meta', false ) ),
				array( 'setMultiple', array( array( 'a' => 1 ), 'options' ) ),
				array( 'deleteMultiple', array( array( 'a' ), $prefix . 'terms' ) ),
				array( 'getMulti', array( array( 'a', 'b' ), array( $prefix . 'posts', 'options' ) ) ),
				array( 'setByKey', array( 'server', 'a', 1, $prefix . 'term_meta' ) ),
				array( 'setByKey', array( 'server', 'posts', 1, $prefix . 'last_changed' ) ),
				array( 'casByKey', array( 'token', 'server', 'a', 1, $prefix . 'category_relationships' ) ),
				array( 'someUnknownMethod', array( 'a', 'posts' ) ),
				array( 'getByKey', array( 'server', 'a', $prefix . 'posts', false ) ),
			),
			$cache->calls
		);
	}

	/**
	 * Tests that properties are read from the wrapped cache.
	 *
	 * @return void
	 */
	public function test_forwards_properties() {
		$cache   = new WP_REST_API_Log_Test_Recording_Cache();
		$wrapper = WP_REST_API_Log_Object_Cache::instance()->wrap( $cache );

		$this->assertSame( $cache, $wrapper->unwrap() );
		$this->assertTrue( isset( $wrapper->calls ) );
		$this->assertSame( array(), $wrapper->calls );

		$wrapper->custom = 'value';
		$this->assertSame( 'value', $cache->custom );

		unset( $wrapper->custom );
		$this->assertFalse( isset( $cache->custom ) );
	}
}
