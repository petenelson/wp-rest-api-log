<?php
/**
 * Class WP_REST_API_Log_Test_REST_API_Log
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for logging REST API requests and purging old entries.
 */
class WP_REST_API_Log_Test_REST_API_Log extends WP_UnitTestCase {

	/**
	 * Sets up each test with logging turned on.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'wp-rest-api-log-settings-general', WP_REST_API_Log_Settings_General::get_default_settings() );
		update_option( 'wp-rest-api-log-settings-routes', WP_REST_API_Log_Settings_Routes::get_default_settings() );
	}

	/**
	 * Builds a request and a response for the logger.
	 *
	 * @param  string $method HTTP method.
	 * @param  string $route  Route.
	 * @param  int    $status Response status.
	 * @return array The request and the response.
	 */
	protected function request_and_response( $method = 'GET', $route = '/wp/v2/posts', $status = 200 ) {

		$request = new WP_REST_Request( $method, $route );
		$request->set_query_params( array( 'per_page' => '5' ) );
		$request->set_body_params( array( 'title' => 'Hello' ) );
		$request->set_body( 'title=Hello' );
		$request->set_header( 'X-Custom', 'custom' );

		$response = new WP_REST_Response( array( 'ok' => true ), $status );

		return array( $request, $response );
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
	 * Tests that the logging hooks are registered.
	 *
	 * @return void
	 */
	public function test_plugins_loaded_adds_hooks() {
		$this->assertSame( 9999, has_filter( 'rest_pre_serve_request', array( 'WP_REST_API_Log', 'log_rest_api_response' ) ) );
		$this->assertSame( 10, has_filter( 'wp-rest-api-log-bypass-insert', array( 'WP_REST_API_Log', 'bypass_common_routes' ) ) );
		$this->assertSame( 10, has_action( 'admin_init', array( 'WP_REST_API_Log', 'create_purge_cron' ) ) );
		$this->assertSame( 10, has_action( 'wp-rest-api-log-purge-old-records', array( 'WP_REST_API_Log', 'purge_old_records' ) ) );
	}

	/**
	 * Tests that a REST API request is written to the log.
	 *
	 * @return void
	 */
	public function test_log_rest_api_response() {

		$user_id = self::factory()->user->create( array( 'user_login' => 'api_user' ) );
		wp_set_current_user( $user_id );

		list( $request, $response ) = $this->request_and_response( 'POST', '/wp/v2/posts', 201 );

		$served = WP_REST_API_Log::log_rest_api_response( false, $response, $request, rest_get_server() );
		$this->assertFalse( $served );

		global $wp_rest_api_log_new_entry_id;
		$entry = new WP_REST_API_Log_Entry( $wp_rest_api_log_new_entry_id );

		$this->assertSame( '/wp/v2/posts', $entry->route );
		$this->assertSame( 'POST', $entry->method );
		$this->assertSame( '201', $entry->status );
		$this->assertSame( 'api_user', $entry->user );
		$this->assertSame( 'title=Hello', $entry->request->body );
		$this->assertSame( array( 'per_page' => '5' ), $entry->request->query_params );
		$this->assertSame( array( 'title' => 'Hello' ), $entry->request->body_params );
		$this->assertSame( 'custom', $entry->request->headers['x_custom'] );
	}

	/**
	 * Tests that nothing is logged when logging is turned off.
	 *
	 * @return void
	 */
	public function test_log_rest_api_response_when_disabled() {

		WP_REST_API_Log_Settings_Base::change_enabled_setting( 'general', 'logging-enabled', false );

		list( $request, $response ) = $this->request_and_response();

		$this->assertTrue( WP_REST_API_Log::log_rest_api_response( true, $response, $request, rest_get_server() ) );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Documents that nothing is logged until the General settings have been
	 * saved at least once.
	 *
	 * Bug: the "logging-enabled" default of "1" is only written to the
	 * database by the activation hook. WP_REST_API_Log_Settings_Base::
	 * setting_is_enabled() falls back to "0", so logging stays off wherever
	 * the activation hook did not run for the site, such as the other sites
	 * of a network-activated multisite install.
	 *
	 * @return void
	 */
	public function test_logging_is_off_when_settings_were_never_saved() {

		delete_option( 'wp-rest-api-log-settings-general' );

		list( $request, $response ) = $this->request_and_response();

		WP_REST_API_Log::log_rest_api_response( false, $response, $request, rest_get_server() );

		// Current behavior: nothing is logged, even though the default is "1".
		$this->assertSame( '1', WP_REST_API_Log_Settings_General::get_default_settings()['logging-enabled'] );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests that the plugin's own routes are not logged.
	 *
	 * @return void
	 */
	public function test_log_rest_api_response_bypasses_own_routes() {

		list( $request, $response ) = $this->request_and_response( 'GET', '/wp-rest-api-log/entries' );

		WP_REST_API_Log::log_rest_api_response( false, $response, $request, rest_get_server() );

		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests that routes excluded by the route filters are not logged, and
	 * that the decision can be filtered.
	 *
	 * @return void
	 */
	public function test_log_rest_api_response_respects_route_filters() {

		update_option(
			'wp-rest-api-log-settings-routes',
			array(
				'route-log-matching-mode' => 'exclude_matches',
				'route-filters'           => '/wp/v2/*',
			)
		);

		list( $request, $response ) = $this->request_and_response( 'GET', '/wp/v2/posts' );

		WP_REST_API_Log::log_rest_api_response( false, $response, $request, rest_get_server() );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );

		// The filter can override the route filters.
		add_filter(
			'wp-rest-api-log-can-log-route',
			function ( $can_log, $route ) {
				$this->assertFalse( $can_log );
				$this->assertSame( '/wp/v2/posts', $route );
				return true;
			},
			10,
			2
		);

		WP_REST_API_Log::log_rest_api_response( false, $response, $request, rest_get_server() );
		$this->assertCount( 1, WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests which routes are bypassed by default.
	 *
	 * @return void
	 */
	public function test_bypass_common_routes() {

		$oembed = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );
		$own    = new WP_REST_Request( 'GET', '/wp-rest-api-log/entries' );
		$posts  = new WP_REST_Request( 'GET', '/wp/v2/posts' );

		$this->assertTrue( WP_REST_API_Log::bypass_common_routes( false, null, $own, null ) );
		$this->assertTrue( WP_REST_API_Log::bypass_common_routes( false, null, $oembed, null ) );
		$this->assertFalse( WP_REST_API_Log::bypass_common_routes( false, null, $posts, null ) );

		// The incoming value is kept for routes that are not bypassed.
		$this->assertTrue( WP_REST_API_Log::bypass_common_routes( true, null, $posts, null ) );

		// oEmbed is logged when the setting is turned off.
		update_option( 'wp-rest-api-log-settings-routes', array( 'ignore-core-oembed' => '0' ) );
		$this->assertFalse( WP_REST_API_Log::bypass_common_routes( false, null, $oembed, null ) );
	}

	/**
	 * Documents that oEmbed requests are logged until the Routes settings
	 * have been saved at least once.
	 *
	 * Bug: the "ignore-core-oembed" default of "1" is only written to the
	 * database by the activation hook, and bypass_common_routes() reads the
	 * setting without a default, so the documented default is not applied
	 * when the option is missing.
	 *
	 * @return void
	 */
	public function test_oembed_is_logged_when_settings_were_never_saved() {

		delete_option( 'wp-rest-api-log-settings-routes' );

		$oembed = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );

		// Current behavior: oEmbed is not bypassed, even though the default is "1".
		$this->assertSame( '1', WP_REST_API_Log_Settings_Routes::get_default_settings()['ignore-core-oembed'] );
		$this->assertFalse( WP_REST_API_Log::bypass_common_routes( false, null, $oembed, null ) );
	}

	/**
	 * Tests reading the response headers. The CLI has no response headers,
	 * so the list is empty.
	 *
	 * @return void
	 */
	public function test_get_response_headers() {
		$this->assertSame( array(), WP_REST_API_Log::get_response_headers( new WP_REST_Response() ) );
	}

	/**
	 * Tests that the purge cron job is only scheduled once.
	 *
	 * @return void
	 */
	public function test_create_purge_cron() {

		wp_clear_scheduled_hook( 'wp-rest-api-log-purge-old-records' );

		WP_REST_API_Log::create_purge_cron();

		$timestamp = wp_next_scheduled( 'wp-rest-api-log-purge-old-records' );
		$this->assertNotFalse( $timestamp );
		$this->assertSame( 'hourly', wp_get_schedule( 'wp-rest-api-log-purge-old-records' ) );

		WP_REST_API_Log::create_purge_cron();
		$this->assertSame( $timestamp, wp_next_scheduled( 'wp-rest-api-log-purge-old-records' ) );

		wp_clear_scheduled_hook( 'wp-rest-api-log-purge-old-records' );
	}

	/**
	 * Tests finding entries older than a number of days.
	 *
	 * @return void
	 */
	public function test_get_old_log_ids() {

		$recent = $this->insert_entry( $this->days_ago( 1 ) );
		$old    = $this->insert_entry( $this->days_ago( 10 ) );
		$oldest = $this->insert_entry( $this->days_ago( 40 ) );

		$this->assertEqualSets( array( $old, $oldest ), WP_REST_API_Log::get_old_log_ids( 5 ) );
		$this->assertEqualSets( array( $oldest ), WP_REST_API_Log::get_old_log_ids( 30 ) );

		// Without a number of days, the purge-days setting is used.
		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '7' );
		$this->assertEqualSets( array( $old, $oldest ), WP_REST_API_Log::get_old_log_ids( false ) );

		// Nothing is returned when no retention period is set.
		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '' );
		$this->assertSame( array(), WP_REST_API_Log::get_old_log_ids( false ) );

		$this->assertInstanceOf( 'WP_Post', get_post( $recent ) );
	}

	/**
	 * Documents that a zero day count selects every entry instead of using
	 * the configured retention period.
	 *
	 * Bug: WP_REST_API_Log::get_old_log_ids() skips the purge-days setting
	 * when $days_old is the integer 0. "wp rest-api-log purge" without a
	 * days argument passes absint( 0 ), so it deletes every entry older than
	 * the current minute, even though its help text says it defaults to the
	 * plugin setting.
	 *
	 * @return void
	 */
	public function test_get_old_log_ids_with_zero_days_selects_everything() {

		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '7' );

		$recent = $this->insert_entry( $this->days_ago( 1 ) );
		$old    = $this->insert_entry( $this->days_ago( 10 ) );

		// Current behavior: the one day old entry is included.
		$this->assertEqualSets( array( $recent, $old ), WP_REST_API_Log::get_old_log_ids( 0 ) );
	}

	/**
	 * Tests purging old entries, including a dry run.
	 *
	 * @return void
	 */
	public function test_purge_old_records() {

		$recent = $this->insert_entry( $this->days_ago( 1 ) );
		$old    = $this->insert_entry( $this->days_ago( 10 ) );
		$oldest = $this->insert_entry( $this->days_ago( 40 ) );

		// A dry run counts, but does not delete.
		$this->assertSame( 2, WP_REST_API_Log::purge_old_records( 5, true ) );
		$this->assertCount( 3, WP_REST_API_Log_DB::get_all_log_ids() );

		$this->assertSame( 1, WP_REST_API_Log::purge_old_records( 30 ) );
		$this->assertNull( get_post( $oldest ) );

		// Without a number of days, the purge-days setting is used.
		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '5' );
		$this->assertSame( 1, WP_REST_API_Log::purge_old_records() );
		$this->assertNull( get_post( $old ) );
		$this->assertInstanceOf( 'WP_Post', get_post( $recent ) );

		// Nothing happens when no retention period is set.
		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '' );
		$this->assertNull( WP_REST_API_Log::purge_old_records() );
		$this->assertInstanceOf( 'WP_Post', get_post( $recent ) );
	}
}
