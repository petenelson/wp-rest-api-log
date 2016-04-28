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

		WP_REST_API_Log_Settings_General::change_enabled_setting( 'general', 'logging-enabled', true, 'WP_REST_API_Log_Settings_General::sanitize_settings' );

		$option = get_option( WP_REST_API_Log_Settings_General::$settings_key );

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

		WP_REST_API_Log_Settings_General::change_enabled_setting( 'general', 'logging-enabled', false, 'WP_REST_API_Log_Settings_General::sanitize_settings' );

		$option = get_option( WP_REST_API_Log_Settings_General::$settings_key );

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

		$option = get_option( WP_REST_API_Log_Settings_General::$settings_key );

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

	/**
	 * Migrates records from the legacy custom tables into custom post type
	 *
	 * ## OPTIONS
	 *
	 * [<days_old>]
	 * Delete entries older than this many days, defaults to whatever you
	 * have configured in the plugin settings
	 *
	 * --dry-run
	 * Shows number of entries that would be deleted but does not
	 * delete them
	 * 
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log purge
	 *
	 *     wp rest-api-log purge 90
	 *
	 * @synopsis [<days_old>] [--dry-run]
	 */
	function purge( $positional_args, $assoc_args = array() ) {

		$days_old     = absint( ! empty( $positional_args[0] ) ? $positional_args[0] : 0 );
		$dry_run      = ! empty( $assoc_args['dry-run'] );

		WP_CLI::Line( "Purging old REST API log entries..." );

		$log = new WP_REST_API_Log();

		$number_deleted = $log->purge_old_records( $days_old, $dry_run );

		WP_CLI::Success( sprintf( "%d entries purged", $number_deleted ) );

	}

}
