<?php
/**
 * Models the request half of a logged REST API call.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_API_Request' ) ) {

	/**
	 * Represents the request portion of a log entry.
	 */
	class WP_REST_API_Log_API_Request extends WP_REST_API_Log_API_Request_Response_Base {

		/**
		 * Body parameters sent with the request.
		 *
		 * @var array
		 */
		public $body_params;

		/**
		 * Query string parameters sent with the request.
		 *
		 * @var array
		 */
		public $query_params;

		/**
		 * Loads the request data for a log entry.
		 *
		 * @param WP_Post|int|null $post Log entry post object or ID.
		 */
		public function __construct( $post = null ) {
			parent::__construct( 'request', $post );
			$this->load();
		}


		/**
		 * Populates the request parameters from post meta.
		 *
		 * @return void
		 */
		private function load() {

			$this->body_params  = parent::get_post_meta_array( 'body_params' );
			$this->query_params = parent::get_post_meta_array( 'query_params' );
		}
	}

}
