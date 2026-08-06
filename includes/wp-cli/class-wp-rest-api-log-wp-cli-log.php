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

	// phpcs:ignore
	/**
	 * Purges old REST API Log records.
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
	function purge( $positional_args, $assoc_args = array() ) { // phpcs:ignore

		$days_old     = absint( ! empty( $positional_args[0] ) ? $positional_args[0] : 0 );
		$dry_run      = ! empty( $assoc_args['dry-run'] );

		WP_CLI::Line( 'Getting old REST API log entries...' );

		$ids = WP_REST_API_Log::get_old_log_ids( $days_old );

		$count          = count( $ids );
		$number_deleted = 0;

		$progress = \WP_CLI\Utils\make_progress_bar( sprintf( 'Deleting %d old log entries', $count ), $count );

		foreach ( $ids as $id ) {
			if ( ! $dry_run ) {
				wp_delete_post( $id, true );
				$number_deleted++;
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::Success( sprintf( '%d entries purged', $number_deleted ) );

	}

	// phpcs:ignore
	/**
	 * Generates sample REST API log entries for testing.
	 *
	 * ## OPTIONS
	 *
	 * [<count>]
	 * Number of sample log entries to generate, defaults to 100
	 *
	 * [--days=<days>]
	 * Spreads the generated entries across this many past days, defaults to 30
	 *
	 * ## EXAMPLES
	 *
	 *     wp rest-api-log generate
	 *
	 *     wp rest-api-log generate 5000
	 *
	 *     wp rest-api-log generate 5000 --days=60
	 *
	 * @synopsis [<count>] [--days=<days>]
	 */
	function generate( $positional_args, $assoc_args = array() ) { // phpcs:ignore

		$count = absint( ! empty( $positional_args[0] ) ? $positional_args[0] : 100 );
		$days  = absint( ! empty( $assoc_args['days'] ) ? $assoc_args['days'] : 30 );

		if ( empty( $count ) ) {
			WP_CLI::Error( 'Please provide a count greater than zero.' );
			return;
		}

		$db = new WP_REST_API_Log_DB();

		$progress = \WP_CLI\Utils\make_progress_bar( sprintf( 'Generating %d sample log entries', $count ), $count );

		for ( $i = 0; $i < $count; $i++ ) {

			$status = self::random_status_code();
			$method = self::random_method();
			$time   = self::random_time( $days );

			$args = array(
				'time'                 => $time,
				'ip_address'           => self::random_ip_address(),
				'user'                 => self::random_user(),
				'http_x_forwarded_for' => '',
				'route'                => self::random_route(),
				'source'               => 'WP REST API',
				'method'               => $method,
				'status'               => $status,
				'request'              => array(
					'body' => self::random_request_body( $method ),
					),
				'response'              => array(
					'body' => self::random_response_body( $status ),
					),
				'milliseconds'         => wp_rand( 5, 1500 ),
				);

			$post_id = $db->insert( $args );

			if ( ! empty( $post_id ) ) {

				global $wpdb;

				$wpdb->update(
					$wpdb->posts,
					array(
						'post_date'         => $time,
						'post_date_gmt'     => get_gmt_from_date( $time ),
						'post_modified'     => $time,
						'post_modified_gmt' => get_gmt_from_date( $time ),
						),
					array(
						'ID' => $post_id, // where clause
						)
				);

			}

			$progress->tick();

		}

		$progress->finish();

		WP_CLI::Success( sprintf( '%d sample log entries generated', $count ) );

	}

	/**
	 * Picks a random HTTP status code, weighted so most entries are 200
	 * with a mix of other common success and error codes.
	 *
	 * @return int
	 */
	private static function random_status_code() {

		$weighted = array(
			200 => 70,
			201 => 5,
			204 => 3,
			400 => 5,
			401 => 3,
			403 => 3,
			404 => 6,
			429 => 2,
			500 => 2,
			503 => 1,
			);

		$roll       = wp_rand( 1, 100 );
		$cumulative = 0;

		foreach ( $weighted as $status => $weight ) {
			$cumulative += $weight;
			if ( $roll <= $cumulative ) {
				return $status;
			}
		}

		return 200;
	}

	/**
	 * Picks a random HTTP method, weighted so GET requests are the
	 * most common.
	 *
	 * @return string
	 */
	private static function random_method() {

		$weighted = array(
			'GET'    => 65,
			'POST'   => 20,
			'PUT'    => 6,
			'PATCH'  => 4,
			'DELETE' => 5,
			);

		$roll       = wp_rand( 1, 100 );
		$cumulative = 0;

		foreach ( $weighted as $method => $weight ) {
			$cumulative += $weight;
			if ( $roll <= $cumulative ) {
				return $method;
			}
		}

		return 'GET';
	}

	/**
	 * Picks a random sample REST route.
	 *
	 * @return string
	 */
	private static function random_route() {

		$routes = array(
			'/wp/v2/posts',
			'/wp/v2/posts/' . wp_rand( 1, 500 ),
			'/wp/v2/pages',
			'/wp/v2/pages/' . wp_rand( 1, 100 ),
			'/wp/v2/media',
			'/wp/v2/media/' . wp_rand( 1, 300 ),
			'/wp/v2/users',
			'/wp/v2/users/' . wp_rand( 1, 20 ),
			'/wp/v2/comments',
			'/wp/v2/categories',
			'/wp/v2/tags',
			'/wp/v2/search',
			'/wp-rest-api-log/v1/entries',
			'/oembed/1.0/embed',
			);

		return $routes[ array_rand( $routes ) ];
	}

	/**
	 * Generates a random IPv4 address.
	 *
	 * @return string
	 */
	private static function random_ip_address() {
		return sprintf( '%d.%d.%d.%d', wp_rand( 1, 223 ), wp_rand( 0, 255 ), wp_rand( 0, 255 ), wp_rand( 1, 254 ) );
	}

	/**
	 * Picks a random existing user login, falling back to an empty
	 * string when the site has no users.
	 *
	 * @return string
	 */
	private static function random_user() {

		static $logins = null;

		if ( null === $logins ) {
			$users  = get_users( array( 'fields' => 'user_login', 'number' => 20 ) );
			$logins = ! empty( $users ) ? $users : array( '' );
		}

		return $logins[ array_rand( $logins ) ];
	}

	/**
	 * Generates a random timestamp within the past number of days.
	 *
	 * @param  int $days
	 * @return string
	 */
	private static function random_time( $days ) {
		$seconds_ago = wp_rand( 0, max( 0, $days ) * DAY_IN_SECONDS );
		return gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $seconds_ago );
	}

	/**
	 * Builds a sample request body, empty for methods that typically
	 * don't send one.
	 *
	 * @param  string $method
	 * @return string
	 */
	private static function random_request_body( $method ) {

		if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return '';
		}

		return wp_json_encode( array( 'sample_param' => wp_rand( 1, 999 ) ) );
	}

	/**
	 * Builds a sample response body appropriate for the given status code.
	 *
	 * @param  int $status
	 * @return array
	 */
	private static function random_response_body( $status ) {

		if ( $status >= 400 ) {
			return array(
				'code'    => 'rest_sample_error',
				'message' => sprintf( 'Sample error response for status %d.', $status ),
				'data'    => array( 'status' => $status ),
				);
		}

		return array(
			'id'    => wp_rand( 1, 1000 ),
			'title' => 'Sample response body',
			);
	}
}
