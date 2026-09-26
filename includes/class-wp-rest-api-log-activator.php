<?php
/**
 * Runs the plugin's activation routine.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

/**
 * Sets up default settings when the plugin is activated.
 */
class WP_REST_API_Log_Activator {

	/**
	 * Creates the default settings and flags the plugin as newly activated.
	 *
	 * @return void
	 */
	public static function activate() {

		WP_REST_API_Log_Settings::create_default_settings();

		// Add an option so we can show the activated admin notice.
		add_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-activated', '1' );
	}
}
