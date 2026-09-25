<?php
/**
 * Class WP_REST_API_Log_Test_ElasticPress
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for logging ElasticPress queries.
 */
class WP_REST_API_Log_Test_ElasticPress extends WP_UnitTestCase {

	use WP_REST_API_Log_Test_Hooks;

	/**
	 * Option name storing the ElasticPress tab settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp-rest-api-log-settings-elasticpress';

	/**
	 * Turns on ElasticPress logging for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		update_option( self::OPTION_KEY, array( 'logging-enabled' => '1' ) );
		update_option(
			'wp-rest-api-log-settings-headers',
			array(
				'redacted-request-headers'  => '',
				'redacted-response-headers' => '',
			)
		);
	}

	/**
	 * Builds an ElasticPress query log item.
	 *
	 * @param  string $url Request URL.
	 * @return array
	 */
	protected function query( $url = 'http://localhost:9200/site-post-1/_search' ) {
		return array(
			'url'         => $url,
			'host'        => 'http://localhost:9200/',
			'time_start'  => 100.25,
			'time_finish' => 100.75,
			'args'        => array(
				'method' => 'POST',
				'body'   => '{"query":{"match_all":{}}}',
			),
			'request'     => array(
				'headers'  => array(
					'content-type' => 'application/json',
				),
				'response' => array(
					'code' => 200,
				),
				'body'     => '{"took":3,"hits":{"total":0}}',
			),
		);
	}

	/**
	 * Tests that the ElasticPress hooks are registered.
	 *
	 * @return void
	 */
	public function test_hooks_are_registered() {
		$this->assert_registers_hooks(
			array( 'WP_REST_API_Log_ElasticPress', 'plugins_loaded' ),
			array(
				array( 'ep_add_query_log', 'WP_REST_API_Log_ElasticPress::log_query', 10 ),
				array( 'ep_post_sync_kill', 'WP_REST_API_Log_ElasticPress::sync_kill', 10 ),
			)
		);
	}

	/**
	 * Documents that log entries are not kept out of ElasticPress syncing.
	 *
	 * Bug: WP_REST_API_Log_ElasticPress::sync_kill() is meant to stop log
	 * entries from being synced ("Don't sync our log entries to
	 * ElasticSearch"), but it sets $kill to false for them instead of true,
	 * so it never stops a sync.
	 *
	 * @return void
	 */
	public function test_sync_kill() {

		// Other post types keep the incoming value.
		$this->assertTrue( WP_REST_API_Log_ElasticPress::sync_kill( true, array( 'post_type' => 'post' ) ) );
		$this->assertFalse( WP_REST_API_Log_ElasticPress::sync_kill( false, array() ) );

		// Current behavior: log entries are not killed, and an earlier kill
		// from another callback is undone.
		$this->assertFalse( WP_REST_API_Log_ElasticPress::sync_kill( false, array( 'post_type' => WP_REST_API_Log_DB::POST_TYPE ) ) );
		$this->assertFalse( WP_REST_API_Log_ElasticPress::sync_kill( true, array( 'post_type' => WP_REST_API_Log_DB::POST_TYPE ) ) );
	}

	/**
	 * Tests that an ElasticPress query is logged.
	 *
	 * @return void
	 */
	public function test_log_query() {

		do_action( 'ep_add_query_log', $this->query() );

		$ids = WP_REST_API_Log_DB::get_all_log_ids();
		$this->assertCount( 1, $ids );

		$entry = new WP_REST_API_Log_Entry( $ids[0] );

		$this->assertSame( 'http://localhost:9200/site-post-1/_search', $entry->route );
		$this->assertSame( 'ElasticPress', $entry->source );
		$this->assertSame( 'POST', $entry->method );
		$this->assertSame( '200', $entry->status );
		$this->assertSame( 500, $entry->milliseconds );
		$this->assertSame( base64_encode( '{"query":{"match_all":{}}}' ), $entry->request->body ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Matches how the body is stored.
		$this->assertSame( array( 'content-type' => 'application/json' ), $entry->response->headers );
		$this->assertSame(
			array(
				'took' => 3,
				'hits' => array( 'total' => 0 ),
			),
			json_decode( $entry->response->body, true )
		);
	}

	/**
	 * Tests that a query with only the minimum data is still logged.
	 *
	 * @return void
	 */
	public function test_log_query_minimal() {

		WP_REST_API_Log_ElasticPress::log_query( array( 'time_start' => 1 ) );

		$ids = WP_REST_API_Log_DB::get_all_log_ids();
		$this->assertCount( 1, $ids );

		$entry = new WP_REST_API_Log_Entry( $ids[0] );

		$this->assertSame( '', $entry->route );
		$this->assertSame( 'ElasticPress', $entry->source );
		$this->assertSame( 'GET', $entry->method );

		// A missing status becomes 0, which wp_set_post_terms() skips.
		$this->assertSame( '', $entry->status );
	}

	/**
	 * Tests the queries that are not logged.
	 *
	 * @return void
	 */
	public function test_log_query_skipped() {

		WP_REST_API_Log_ElasticPress::log_query( array() );
		WP_REST_API_Log_ElasticPress::log_query( 'not an array' );

		$skipped_urls = array(
			'http://localhost:9200/_stats/indexing',
			'http://localhost:9200/site-post-1/post/_bulk',
			'http://localhost:9200/_nodes/plugins',
			'http://localhost:9200/_nodes?plugin=true',
			'http://localhost:9200/site-post-1/post/123',
		);

		foreach ( $skipped_urls as $url ) {
			WP_REST_API_Log_ElasticPress::log_query( $this->query( $url ) );
		}

		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );

		// The decision can be filtered.
		add_filter( 'wp-rest-api-log-elasticpress-log-query', '__return_true' );
		WP_REST_API_Log_ElasticPress::log_query( $this->query( 'http://localhost:9200/_stats/indexing' ) );
		$this->assertCount( 1, WP_REST_API_Log_DB::get_all_log_ids() );

		add_filter( 'wp-rest-api-log-elasticpress-log-query', '__return_false', 11 );
		WP_REST_API_Log_ElasticPress::log_query( $this->query() );
		$this->assertCount( 1, WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests that nothing is logged when ElasticPress logging is turned off.
	 *
	 * @return void
	 */
	public function test_log_query_disabled() {

		update_option( self::OPTION_KEY, array( 'logging-enabled' => '0' ) );

		WP_REST_API_Log_ElasticPress::log_query( $this->query() );

		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Documents that ElasticPress queries are not logged until the
	 * ElasticPress tab has been saved.
	 *
	 * Bug: the ElasticPress "logging-enabled" default of "1" is never
	 * written to the database, because
	 * WP_REST_API_Log_Settings::create_default_settings() does not create
	 * the ElasticPress option, and the setting is read with a "0" fallback.
	 *
	 * @return void
	 */
	public function test_log_query_off_by_default() {

		delete_option( self::OPTION_KEY );

		WP_REST_API_Log_ElasticPress::log_query( $this->query() );

		// Current behavior: nothing is logged, even though the default is "1".
		$this->assertSame( '1', WP_REST_API_Log_Settings_ElasticPress::get_default_settings()['logging-enabled'] );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}
}
