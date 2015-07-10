<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Common' ) ) {

	class WP_REST_API_Log_Common {

		static $plugin_name    = 'wp-rest-api-log';
		static $version        = '2015-07-03-01';

		const TEXT_DOMAIN      = 'wp-rest-api-log';


		public function plugins_loaded() {

		}


		static public function current_milliseconds() {
			list( $usec, $sec ) = explode( " ", microtime() );
			return ( ( (float)$usec + (float)$sec ) ) * 1000;
		}


		static public function api_is_enabled() {
			return class_exists( 'WP_REST_Server' ) && apply_filters( 'rest_enabled', true );
		}


	} // end class

}
