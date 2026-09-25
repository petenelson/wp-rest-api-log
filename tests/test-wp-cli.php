<?php
/**
 * Class WP_REST_API_Log_Test_WP_CLI
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the "wp rest-api-log" WP-CLI commands, run against stand-ins for
 * the WP-CLI classes.
 */
class WP_REST_API_Log_Test_WP_CLI extends WP_UnitTestCase {

	/**
	 * Command under test.
	 *
	 * @var WP_REST_API_Log_WP_CLI_Log
	 */
	protected $command;

	/**
	 * Loads the WP-CLI stand-ins and registers the commands.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Signature is fixed by the test case.
		require_once __DIR__ . '/stubs/wp-cli.php';
		require_once __DIR__ . '/stubs/wp-cli-utils.php';
		require WP_REST_API_LOG_PATH . 'includes/wp-cli/setup.php';
	}

	/**
	 * Sets up each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		WP_CLI::$messages                        = array();
		WP_REST_API_Log_Test_Progress_Bar::$bars = array();

		$this->command = new WP_REST_API_Log_WP_CLI_Log();
	}

	/**
	 * Inserts a log entry with the given post date.
	 *
	 * @param  string $date Post date.
	 * @return int Post ID.
	 */
	protected function insert_entry( $date ) {

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->insert( array( 'route' => '/wp/v2/posts' ) );

		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_date'     => $date,
				'post_date_gmt' => get_gmt_from_date( $date ),
			)
		);

		return $post_id;
	}

	/**
	 * Returns a date the given number of days in the past.
	 *
	 * @param  int $days Days ago.
	 * @return string MySQL date.
	 */
	protected function days_ago( $days ) {
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date, WordPress.DateTime.CurrentTimeTimestamp.Requested -- Matches the site-local time used by the purge.
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) );
	}

	/**
	 * Returns the last recorded WP-CLI message.
	 *
	 * @return array Message type and text.
	 */
	protected function last_message() {
		return end( WP_CLI::$messages );
	}

	/**
	 * Tests that the command is registered.
	 *
	 * @return void
	 */
	public function test_command_is_registered() {
		$this->assertSame( 'WP_REST_API_Log_WP_CLI_Log', WP_CLI::$commands['rest-api-log'] );
	}

	/**
	 * Tests enabling, disabling and reading the logging status.
	 *
	 * @return void
	 */
	public function test_enable_disable_status() {

		delete_option( 'wp-rest-api-log-settings-general' );

		$this->command->status();
		$this->assertSame( array( 'line', 'REST API Log is not enabled' ), $this->last_message() );

		$this->command->enable();
		$this->assertSame( array( 'success', 'REST API Log enabled' ), $this->last_message() );
		$this->assertTrue( WP_REST_API_Log_Settings_Base::setting_is_enabled( 'general', 'logging-enabled' ) );

		$this->command->status();
		$this->assertSame( array( 'line', 'REST API Log is enabled' ), $this->last_message() );

		$this->command->disable();
		$this->assertSame( array( 'success', 'REST API Log disabled' ), $this->last_message() );
		$this->assertFalse( WP_REST_API_Log_Settings_Base::setting_is_enabled( 'general', 'logging-enabled' ) );
	}

	/**
	 * Tests that enable reports an error when the setting is not saved.
	 *
	 * @return void
	 */
	public function test_enable_error() {

		update_option( 'wp-rest-api-log-settings-general', array( 'logging-enabled' => '0' ) );

		add_filter(
			'pre_update_option_wp-rest-api-log-settings-general',
			function ( $value, $old_value ) {
				return $old_value;
			},
			10,
			2
		);

		$this->expectException( 'WP_REST_API_Log_Test_WP_CLI_Error' );
		$this->expectExceptionMessage( 'REST API Log was not enabled' );

		$this->command->enable();
	}

	/**
	 * Tests that disable reports an error when the setting is not saved.
	 *
	 * @return void
	 */
	public function test_disable_error() {

		update_option( 'wp-rest-api-log-settings-general', array( 'logging-enabled' => '1' ) );

		add_filter(
			'pre_update_option_wp-rest-api-log-settings-general',
			function ( $value, $old_value ) {
				return $old_value;
			},
			10,
			2
		);

		$this->expectException( 'WP_REST_API_Log_Test_WP_CLI_Error' );
		$this->expectExceptionMessage( 'REST API Log was not disabled' );

		$this->command->disable();
	}

	/**
	 * Tests the migrate command when there is nothing to migrate.
	 *
	 * @return void
	 */
	public function test_migrate_without_legacy_entries() {

		$this->command->migrate();

		$this->assertSame( array( 'line', 'There are no more log entries that need to be migrated.' ), $this->last_message() );
	}

	/**
	 * Tests purging entries older than a number of days, including a dry run.
	 *
	 * @return void
	 */
	public function test_purge() {

		$recent = $this->insert_entry( $this->days_ago( 1 ) );
		$old    = $this->insert_entry( $this->days_ago( 10 ) );

		$this->command->purge( array( '5' ), array( 'dry-run' => true ) );

		$this->assertSame( array( 'success', '0 entries purged' ), $this->last_message() );
		$this->assertSame( 'Deleting 1 old log entries', WP_REST_API_Log_Test_Progress_Bar::$bars[0]->message );
		$this->assertSame( 1, WP_REST_API_Log_Test_Progress_Bar::$bars[0]->ticks );
		$this->assertInstanceOf( 'WP_Post', get_post( $old ) );

		$this->command->purge( array( '5' ) );

		$this->assertSame( array( 'success', '1 entries purged' ), $this->last_message() );
		$this->assertTrue( WP_REST_API_Log_Test_Progress_Bar::$bars[1]->finished );
		$this->assertNull( get_post( $old ) );
		$this->assertInstanceOf( 'WP_Post', get_post( $recent ) );
	}

	/**
	 * Documents that purging without a number of days deletes every entry.
	 *
	 * Bug: the help text says "wp rest-api-log purge" defaults to the
	 * purge-days setting, but the command passes absint( 0 ) to
	 * WP_REST_API_Log::get_old_log_ids(), which only reads the setting for
	 * values other than the integer 0. Every entry older than the current
	 * minute is deleted.
	 *
	 * @return void
	 */
	public function test_purge_without_days_deletes_everything() {

		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '7' );

		$recent = $this->insert_entry( $this->days_ago( 1 ) );
		$old    = $this->insert_entry( $this->days_ago( 10 ) );

		$this->command->purge( array() );

		// Current behavior: the one day old entry is deleted too.
		$this->assertSame( array( 'success', '2 entries purged' ), $this->last_message() );
		$this->assertNull( get_post( $recent ) );
		$this->assertNull( get_post( $old ) );
	}

	/**
	 * Tests that sample entries cannot be generated in production.
	 *
	 * @return void
	 */
	public function test_generate_blocked_in_production() {

		add_filter( 'wp_rest_api_log_generate_allowed', '__return_false' );

		$this->expectException( 'WP_REST_API_Log_Test_WP_CLI_Error' );
		$this->expectExceptionMessage( 'not allowed in production' );

		$this->command->generate( array( '5' ) );
	}

	/**
	 * Tests that a count of zero is rejected.
	 *
	 * @return void
	 */
	public function test_generate_requires_a_count() {

		add_filter( 'wp_rest_api_log_generate_allowed', '__return_true' );

		$this->expectException( 'WP_REST_API_Log_Test_WP_CLI_Error' );
		$this->expectExceptionMessage( 'count greater than zero' );

		$this->command->generate( array( '0' ) );
	}

	/**
	 * Tests generating backdated sample entries.
	 *
	 * @return void
	 */
	public function test_generate() {

		add_filter( 'wp_rest_api_log_generate_allowed', '__return_true' );

		$this->command->generate( array( '25' ), array( 'days' => '3' ) );

		$this->assertSame( array( 'success', '25 sample log entries generated' ), $this->last_message() );
		$this->assertSame( 25, WP_REST_API_Log_Test_Progress_Bar::$bars[0]->ticks );

		$ids = WP_REST_API_Log_DB::get_all_log_ids();
		$this->assertCount( 25, $ids );

		$oldest_allowed = $this->days_ago( 3 );
		$methods        = WP_REST_API_Log_Common::valid_methods();

		foreach ( $ids as $id ) {
			$entry = new WP_REST_API_Log_Entry( $id );

			$this->assertGreaterThanOrEqual( $oldest_allowed, $entry->time );
			$this->assertSame( $entry->time, get_post( $id )->post_modified );
			$this->assertContains( $entry->method, $methods );
			$this->assertContains( (int) $entry->status, array( 200, 201, 204, 400, 401, 403, 404, 429, 500, 503 ) );
			$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+\.\d+$/', $entry->ip_address );
			$this->assertStringStartsWith( '/', $entry->route );
			$this->assertGreaterThanOrEqual( 5, $entry->milliseconds );

			$body = json_decode( $entry->response->body, true );
			if ( (int) $entry->status >= 400 ) {
				$this->assertSame( 'rest_sample_error', $body['code'] );
			} else {
				$this->assertSame( 'Sample response body', $body['title'] );
			}

			if ( in_array( $entry->method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
				$this->assertArrayHasKey( 'sample_param', json_decode( $entry->request->body, true ) );
			} else {
				$this->assertSame( '', $entry->request->body );
			}
		}
	}
}
