<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_i18n' ) ) {

	class WP_REST_API_Log_i18n {


		public function plugins_loaded() {

			load_plugin_textdomain(
				WP_REST_API_Log_Common::TEXT_DOMAIN,
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);

		}


	} // end class

}
