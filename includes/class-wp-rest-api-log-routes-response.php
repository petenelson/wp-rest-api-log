<?php
/**
 * Response object returned when listing the registered REST API routes.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Routes_Response' ) ) {

	/**
	 * Wraps the list of routes exposed by the REST API.
	 */
	class WP_REST_API_Log_Routes_Response extends WP_REST_API_Log_API_Response_Base {

		/**
		 * The registered REST API routes.
		 *
		 * @var array
		 */
		public $routes = array();

		/**
		 * Builds the response from a list of routes.
		 *
		 * @param array|null $data Registered route names.
		 */
		public function __construct( $data = null ) {

			if ( is_array( $data ) ) {
				$this->populate_response( $data );
			}
		}


		/**
		 * Copies the route list onto this response.
		 *
		 * @param array $data Registered route names.
		 * @return void
		 */
		private function populate_response( $data ) {

			$this->routes           = $data;
			$this->records_affected = count( $this->routes );
		}
	}

}
