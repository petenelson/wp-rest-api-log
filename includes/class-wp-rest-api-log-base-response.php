<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_API_Base_Response' ) ) {

	class WP_REST_API_Log_API_Base_Response {

		var $records_affected = 0;
		var $args;

	}

}
