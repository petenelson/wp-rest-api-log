<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Entries_Response' ) ) {

	class WP_REST_API_Log_Entries_Response extends WP_REST_API_Log_API_Response_Base {

		var $paged_records = array();
		var $entries_html = '';

		public function __construct( $data = null ) {

			if ( is_object( $data ) ) {
				$this->populate_response( $data );
			}

		}


		private function populate_response( $data ) {

			if ( ! empty( $data->paged_records ) ) {
				$this->paged_records        = $data->paged_records;
				$this->records_affected     = $data->total_records;
				$this->args                 = $data->args;
			}

		}


	}

}

