<?php
/**
 * Response object returned when log entries are deleted.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Delete_Response' ) ) {

	/**
	 * Describes the outcome of a delete request against the log.
	 */
	class WP_REST_API_Log_Delete_Response extends WP_REST_API_Log_API_Response_Base {

		/**
		 * Cut-off date used to select the entries that were deleted.
		 *
		 * @var string
		 */
		public $older_than_date = '';

		/**
		 * Builds the response from the result of a delete operation.
		 *
		 * @param object|null $data Delete result data.
		 */
		public function __construct( $data = null ) {

			if ( is_object( $data ) ) {
				$this->populate_response( $data );
			}
		}


		/**
		 * Copies the delete result onto this response.
		 *
		 * @param object $data Delete result data.
		 * @return void
		 */
		private function populate_response( $data ) {

			$this->args             = $data->args;
			$this->older_than_date  = $data->older_than_date;
			$this->records_affected = $data->records_affected;
		}
	}

}
