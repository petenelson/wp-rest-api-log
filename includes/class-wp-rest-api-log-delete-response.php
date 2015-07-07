<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Delete_Response' ) ) {

	class WP_REST_API_Log_Delete_Response extends WP_REST_API_Log_API_Response_Base {

		var $older_than_date = '';

		public function __construct( $data = null ) {

			if ( is_object( $data ) ) {
				$this->populate_response( $data );
			}

		}


		private function populate_response( $data ) {

			$this->args               = $data->args;
			$this->older_than_date    = $data->older_than_date;
			$this->records_affected   = $data->records_affected;

		}


	}

}

