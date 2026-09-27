<?php
/**
 * Stand-in object cache used by the object cache wrapper tests.
 *
 * @package wp-rest-api-log
 */

/**
 * Stand-in object cache that records each method call.
 */
class WP_REST_API_Log_Test_Recording_Cache {

	/**
	 * Method calls, each a method name and its arguments.
	 *
	 * @var array
	 */
	public $calls = array();

	/**
	 * Custom property used by the property forwarding test.
	 *
	 * @var string
	 */
	public $custom = '';

	/**
	 * Records a getByKey() call and reports the key as found.
	 *
	 * @param  string     $server_key Server key.
	 * @param  int|string $key        Cache key.
	 * @param  string     $group      Cache group.
	 * @param  bool       $force      Whether to skip the local cache.
	 * @param  bool|null  $found      Set to true.
	 * @return string
	 */
	public function getByKey( $server_key, $key, $group, $force, &$found = null ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the drop-in's method name.
		$this->calls[] = array( 'getByKey', array( $server_key, $key, $group, $force ) );
		$found         = true;
		return 'value';
	}

	/**
	 * Records any other method call.
	 *
	 * @param  string $name      Method name.
	 * @param  array  $arguments Method arguments.
	 * @return bool
	 */
	public function __call( $name, $arguments ) {
		$this->calls[] = array( $name, $arguments );
		return true;
	}
}
