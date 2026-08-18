<?php
/**
 * Base class for the plugin's internal API response objects.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_API_Response_Base' ) ) {

	/**
	 * Shared state for responses returned by the plugin's own REST routes.
	 */
	class WP_REST_API_Log_API_Response_Base {

		/**
		 * Number of log entries affected by the operation.
		 *
		 * @var int
		 */
		public $records_affected = 0;

		/**
		 * Arguments the operation was called with.
		 *
		 * @var array
		 */
		public $args;
	}

}
