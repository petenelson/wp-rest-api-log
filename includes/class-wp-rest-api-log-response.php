<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_API_Response' ) ) {

	class WP_REST_API_Log_API_Response extends WP_REST_API_Log_API_Request_Response_Base {


		public function __construct( $post = null ) {
			parent::__construct( 'response', $post );
		}


	}

}
