<?php
/**
 * Keeps cached log data apart from the site's cached content.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Object_Cache' ) ) {

	/**
	 * Wraps the site's object cache while $wpdb is switched to the custom log
	 * tables.
	 *
	 * The custom tables reuse core's table structure, so post and term IDs in
	 * them overlap the site's own IDs. Core caches posts, meta and terms by ID
	 * alone, so without this wrapper a log entry could be read back as a site
	 * post, or a site term as a log entry's HTTP method. The wrapper renames
	 * the post and term cache groups and passes every other group, such as
	 * options and users, straight through to the site's cache.
	 */
	class WP_REST_API_Log_Object_Cache {

		/**
		 * Prefix added to the name of each isolated cache group.
		 *
		 * @var string
		 */
		const GROUP_PREFIX = 'wp-rest-api-log-';

		/**
		 * Single instance of this class.
		 *
		 * @var WP_REST_API_Log_Object_Cache|null
		 */
		private static $instance = null;

		/**
		 * The object cache being wrapped.
		 *
		 * @var object|null
		 */
		private $cache = null;

		/**
		 * Position of the group argument for each cache method, used when a
		 * method is forwarded through __call().
		 *
		 * Covers core's WP_Object_Cache, the Memcached drop-in that ships with
		 * wordpress-develop's test suite (its camelCase methods), and drop-ins
		 * that follow either naming. Any other method is forwarded unchanged.
		 *
		 * @var array
		 */
		private static $group_arguments = array(
			'add'             => 2,
			'addByKey'        => 3,
			'addMultiple'     => 1,
			'add_multiple'    => 1,
			'append'          => 2,
			'appendByKey'     => 3,
			'cas'             => 3,
			'casByKey'        => 4,
			'decr'            => 2,
			'decrement'       => 2,
			'delete'          => 1,
			'deleteByKey'     => 2,
			'deleteMultiple'  => 1,
			'delete_multiple' => 1,
			'flush_group'     => 0,
			'getDelayed'      => 1,
			'getDelayedByKey' => 2,
			'getMulti'        => 1,
			'getMultiByKey'   => 2,
			'getMultiple'     => 1,
			'get_multiple'    => 1,
			'incr'            => 2,
			'increment'       => 2,
			'prepend'         => 2,
			'prependByKey'    => 3,
			'replace'         => 2,
			'replaceByKey'    => 3,
			'set'             => 2,
			'setByKey'        => 3,
			'setMulti'        => 1,
			'setMultiByKey'   => 2,
			'setMultiple'     => 1,
			'set_multiple'    => 1,
		);

		/**
		 * Position of the key argument for methods that take a single key,
		 * used to isolate only the post and term "last_changed" entries.
		 *
		 * @var array
		 */
		private static $key_arguments = array(
			'add'          => 0,
			'addByKey'     => 1,
			'append'       => 0,
			'appendByKey'  => 1,
			'cas'          => 1,
			'casByKey'     => 2,
			'decr'         => 0,
			'decrement'    => 0,
			'delete'       => 0,
			'deleteByKey'  => 1,
			'incr'         => 0,
			'increment'    => 0,
			'prepend'      => 0,
			'prependByKey' => 1,
			'replace'      => 0,
			'replaceByKey' => 1,
			'setByKey'     => 1,
			'set'          => 0,
		);

		/**
		 * Gets the single instance of this class.
		 *
		 * @return WP_REST_API_Log_Object_Cache
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Sets the object cache to wrap.
		 *
		 * @param  object $cache The site's object cache.
		 * @return WP_REST_API_Log_Object_Cache
		 */
		public function wrap( $cache ) {
			$this->cache = $cache;
			return $this;
		}

		/**
		 * Gets the wrapped object cache.
		 *
		 * @return object|null
		 */
		public function unwrap() {
			return $this->cache;
		}

		/**
		 * Gets the names of the cache groups that hold post and term data.
		 *
		 * Taxonomy relationship groups ("{$taxonomy}_relationships") are
		 * matched by suffix in is_isolated_group().
		 *
		 * @return array
		 */
		public static function get_isolated_groups() {
			return apply_filters(
				WP_REST_API_Log_Common::PLUGIN_NAME . '-isolated-cache-groups',
				array(
					'counts',
					'post-queries',
					'post_meta',
					'post_parent',
					'posts',
					'term-queries',
					'term_meta',
					'terms',
					'timeinfo',
				)
			);
		}

		/**
		 * Determines whether a cache group holds post or term data.
		 *
		 * @param  string $group Cache group name.
		 * @return bool
		 */
		public static function is_isolated_group( $group ) {
			return in_array( $group, self::get_isolated_groups(), true )
				|| '_relationships' === substr( $group, -14 );
		}

		/**
		 * Gets the cache group to use while switched to the custom tables.
		 *
		 * Only the "posts" and "terms" entries of the "last_changed" group are
		 * isolated, so logging doesn't invalidate the site's cached post and
		 * term queries, while other entries, such as "users", stay shared.
		 *
		 * Some drop-in methods, such as getMulti(), accept a list of groups;
		 * each one is mapped.
		 *
		 * @param  mixed $group Cache group name, or a list of them.
		 * @param  mixed $key   Optional. Cache key, when the call has one.
		 * @return mixed
		 */
		public static function map_group( $group, $key = null ) {
			if ( is_array( $group ) ) {
				return array_map( array( __CLASS__, 'map_group' ), $group );
			}

			if ( ! is_string( $group ) || '' === $group ) {
				return $group;
			}

			if ( 'last_changed' === $group ) {
				return in_array( $key, array( 'posts', 'terms' ), true ) ? self::GROUP_PREFIX . $group : $group;
			}

			return self::is_isolated_group( $group ) ? self::GROUP_PREFIX . $group : $group;
		}

		/**
		 * Gets a value from the cache.
		 *
		 * Defined explicitly because $found is passed by reference, which
		 * __call() can't forward. Extra arguments some persistent cache
		 * drop-ins accept are forwarded by value.
		 *
		 * @param  int|string $key     Cache key.
		 * @param  string     $group   Optional. Cache group.
		 * @param  bool       $force   Optional. Whether to skip the local cache.
		 * @param  bool|null  $found   Optional. Set to whether the key was found.
		 * @param  mixed      ...$args Further arguments for the wrapped cache.
		 * @return mixed
		 */
		public function get( $key, $group = '', $force = false, &$found = null, ...$args ) {
			return $this->cache->get( $key, self::map_group( $group, $key ), $force, $found, ...$args );
		}

		/**
		 * Gets a value from the cache on a specific server, as supported by
		 * the Memcached drop-in in wordpress-develop's test suite.
		 *
		 * Defined explicitly because $found is passed by reference.
		 *
		 * @param  string     $server_key Server key.
		 * @param  int|string $key        Cache key.
		 * @param  string     $group      Optional. Cache group.
		 * @param  bool       $force      Optional. Whether to skip the local cache.
		 * @param  bool|null  $found      Optional. Set to whether the key was found.
		 * @param  mixed      ...$args    Further arguments for the wrapped cache.
		 * @return mixed
		 */
		public function getByKey( $server_key, $key, $group = 'default', $force = false, &$found = null, ...$args ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the drop-in's method name.
			return $this->cache->getByKey( $server_key, $key, self::map_group( $group, $key ), $force, $found, ...$args );
		}

		/**
		 * Forwards any other cache method, renaming its group argument.
		 *
		 * @param  string $name      Method name.
		 * @param  array  $arguments Method arguments.
		 * @return mixed
		 */
		public function __call( $name, $arguments ) {
			if ( isset( self::$group_arguments[ $name ] ) ) {
				$group_index = self::$group_arguments[ $name ];

				if ( array_key_exists( $group_index, $arguments ) ) {
					$key = null;

					if ( isset( self::$key_arguments[ $name ] ) && array_key_exists( self::$key_arguments[ $name ], $arguments ) ) {
						$key = $arguments[ self::$key_arguments[ $name ] ];
					}

					$arguments[ $group_index ] = self::map_group( $arguments[ $group_index ], $key );
				}
			}

			return call_user_func_array( array( $this->cache, $name ), $arguments );
		}

		/**
		 * Reads a property of the wrapped cache.
		 *
		 * @param  string $name Property name.
		 * @return mixed
		 */
		public function __get( $name ) {
			return $this->cache->$name;
		}

		/**
		 * Sets a property of the wrapped cache.
		 *
		 * @param  string $name  Property name.
		 * @param  mixed  $value Property value.
		 * @return void
		 */
		public function __set( $name, $value ) {
			$this->cache->$name = $value;
		}

		/**
		 * Checks whether a property of the wrapped cache is set.
		 *
		 * @param  string $name Property name.
		 * @return bool
		 */
		public function __isset( $name ) {
			return isset( $this->cache->$name );
		}

		/**
		 * Unsets a property of the wrapped cache.
		 *
		 * @param  string $name Property name.
		 * @return void
		 */
		public function __unset( $name ) {
			unset( $this->cache->$name );
		}
	}
}
