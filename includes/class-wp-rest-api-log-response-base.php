<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_API_Response_Base' ) ) {

	class WP_REST_API_Log_API_Response_Base {

		var $records_affected = 0;
		var $args;

	}

}
