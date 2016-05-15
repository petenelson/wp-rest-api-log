<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

class WP_REST_API_Log_Activator {


	public static function activate() {

		WP_REST_API_Log_Settings::create_default_settings();

		// add an option so we can show the activated admin notice
		add_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-activated', '1' );

	}



}
