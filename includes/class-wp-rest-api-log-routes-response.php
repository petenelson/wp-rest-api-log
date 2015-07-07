<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Routes_Response' ) ) {

	class WP_REST_API_Log_Routes_Response extends WP_REST_API_Log_API_Response_Base {

		var $routes = array();

		public function __construct( $data = null ) {

			if ( is_array( $data ) ) {
				$this->populate_response( $data );
			}

		}


		private function populate_response( $data ) {

			$this->routes = $data;
			$this->records_affected = count( $this->routes );

		}



	}

}