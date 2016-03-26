<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_API_Request' ) ) {

	class WP_REST_API_Log_API_Request extends WP_REST_API_Log_API_Request_Response_Base {

		public $body_params;
		public $query_params;

		public function __construct( $post = null ) {
			parent::__construct( 'request', $post );
			$this->load();
		}


		private function load() {

			$this->body_params     = parent::get_post_meta_array( 'body_params' );
			$this->query_params    = parent::get_post_meta_array( 'query_params' );

		}

	}

}
