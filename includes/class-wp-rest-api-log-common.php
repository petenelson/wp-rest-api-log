<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Common' ) ) {

	class WP_REST_API_Log_Common {

		static $plugin_name    = 'wp-rest-api-log';
		static $version        = '2015-07-03-01';


		public function plugins_loaded() {

		}


	} // end class

}
