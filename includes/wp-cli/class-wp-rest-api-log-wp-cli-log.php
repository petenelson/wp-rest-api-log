<?php

class WP_REST_API_Log_WP_CLI_Log extends WP_CLI_Command  {

	/**
	 * Enables REST API Logging
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log enable
	 *
	 */
	function enable() {

		$settings = new WP_REST_API_Log_Settings();
		$settings->update_setting( $settings->settings_key_general, 'logging-enabled', '1' );

		$option = get_option( $settings->settings_key_general );

		if ( ! empty( $option ) && isset( $option['logging-enabled'] ) && '1' === $option['logging-enabled'] ) {
			WP_CLI::Success( "REST API Log enabled" );	
		} else {
			WP_CLI::Error( "REST API Log was not enabled" );
		}

	}

	/**
	 * Disables REST API Logging
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log disable
	 *
	 */
	function disable() {

		$settings = new WP_REST_API_Log_Settings();
		$settings->update_setting( $settings->settings_key_general, 'logging-enabled', '0' );

		$option = get_option( $settings->settings_key_general );

		if ( ! empty( $option ) && isset( $option['logging-enabled'] ) && '0' === $option['logging-enabled'] ) {
			WP_CLI::Success( "REST API Log disabled" );	
		} else {
			WP_CLI::Error( "REST API Log was not disabled" );
		}

	}

	/**
	 * Gets the current status of the REST API Log
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log status
	 *
	 */
	function status() {

		$settings = new WP_REST_API_Log_Settings();

		$option = get_option( $settings->settings_key_general );

		if ( ! empty( $option ) && isset( $option['logging-enabled'] ) && '1' === $option['logging-enabled'] ) {
			WP_CLI::Line( "REST API Log is enabled" );	
		} else {
			WP_CLI::Line( "REST API Log is not enabled" );
		}

	}

}
