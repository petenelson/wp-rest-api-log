<?php
/**
 * Models the response half of a logged REST API call.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_API_Response' ) ) {

	/**
	 * Represents the response portion of a log entry.
	 */
	class WP_REST_API_Log_API_Response extends WP_REST_API_Log_API_Request_Response_Base {

		/**
		 * Loads the response data for a log entry.
		 *
		 * @param WP_Post|int|null $post Log entry post object or ID.
		 */
		public function __construct( $post = null ) {
			parent::__construct( 'response', $post );
		}
	}

}
