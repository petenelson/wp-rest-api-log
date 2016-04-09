<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Common' ) ) {

	class WP_REST_API_Log_Common {

		const PLUGIN_NAME      = 'wp-rest-api-log';
		const VERSION          = '2016-04-09-01';
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


		static public function valid_methods() {
			return apply_filters( self::PLUGIN_NAME . '-valid-methods', array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ) );
		}


		static public function is_valid_method( $method ) {
			return apply_filters( self::PLUGIN_NAME . '-is-method-valid', in_array( $method, self::valid_methods() ) );
		}


	} // end class

}
