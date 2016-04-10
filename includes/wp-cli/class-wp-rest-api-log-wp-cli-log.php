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

	/**
	 * Migrates records from the legacy custom tables into custom post type
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log migrate
	 *
	 */
	function migrate() {

		WP_CLI::Line( "Getting log entries that need to be migrated..." );

		$db = new WP_REST_API_Log_DB();

		$ids = $db->get_log_ids_to_migrate();

		$count = count( $ids );
		if ( 0 === $count ) {
			WP_CLI::Line( "There are no more log entries that need to be migrated." );
			return;
		}

		$progress_bar = WP_CLI\Utils\make_progress_bar( "Migrating {$count} entries:", $count, 1 );
		$progress_bar->display();

		foreach ( $ids as $id  ) {
			$db->migrate_db_record( $id );
			$progress_bar->tick();
		}

		$progress_bar->finish();

		WP_CLI::Success( "Log entries migrated" );

	}

}
