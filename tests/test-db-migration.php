<?php
/**
 * Class WP_REST_API_Log_Test_DB_Migration
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for migrating entries from the legacy custom tables.
 *
 * The legacy tables are created as real tables rather than the temporary
 * tables the test suite normally uses, because get_log_ids_to_migrate() looks
 * for them with SHOW TABLES, which does not list temporary tables.
 */
class WP_REST_API_Log_Test_DB_Migration extends WP_UnitTestCase {

	/**
	 * Creates the legacy log tables.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test fixture tables.
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wp_rest_api_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				time datetime NOT NULL,
				ip_address varchar(50) NOT NULL DEFAULT '',
				route varchar(255) NOT NULL DEFAULT '',
				method varchar(20) NOT NULL DEFAULT '',
				status int NOT NULL DEFAULT 0,
				request_body longtext,
				response_body longtext,
				milliseconds int NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			)"
		);
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wp_rest_api_logmeta (
				meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				log_id bigint(20) unsigned NOT NULL,
				meta_type varchar(20) NOT NULL DEFAULT '',
				meta_request_response varchar(20) NOT NULL DEFAULT '',
				meta_key varchar(255) NOT NULL DEFAULT '',
				meta_value longtext,
				PRIMARY KEY (meta_id)
			)"
		);
		// phpcs:enable

		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
	}

	/**
	 * Drops the legacy log tables once the test's transaction has been rolled
	 * back, so the implicit commit from DROP TABLE does not keep any data.
	 *
	 * @return void
	 */
	public function tear_down() {
		parent::tear_down();

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test fixture tables.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_rest_api_log" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_rest_api_logmeta" );
		// phpcs:enable
	}

	/**
	 * Inserts a row into the legacy log table.
	 *
	 * @param  array $row  Column values.
	 * @param  array $meta Meta rows for the entry, each one a list of
	 *                     meta type, request or response, key and value.
	 * @return int Legacy log ID.
	 */
	protected function insert_legacy_log( $row, $meta = array() ) {

		global $wpdb;

		$row = wp_parse_args(
			$row,
			array(
				'time'          => '2016-05-01 10:20:30',
				'ip_address'    => '10.1.1.1',
				'route'         => '/wp/v2/posts',
				'method'        => 'GET',
				'status'        => 200,
				'request_body'  => '',
				'response_body' => '{"id":1}',
				'milliseconds'  => 25,
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture table.
		$wpdb->insert( $wpdb->prefix . 'wp_rest_api_log', $row );
		$log_id = (int) $wpdb->insert_id;

		foreach ( $meta as $meta_row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test fixture table.
			$wpdb->insert(
				$wpdb->prefix . 'wp_rest_api_logmeta',
				array_combine(
					array( 'log_id', 'meta_type', 'meta_request_response', 'meta_key', 'meta_value' ),
					array_merge( array( $log_id ), $meta_row )
				)
			);
		}

		return $log_id;
	}

	/**
	 * Tests that a legacy row is migrated to a log entry post.
	 *
	 * @return void
	 */
	public function test_migrate_db_record() {

		$log_id = $this->insert_legacy_log(
			array(
				'method'       => 'POST',
				'status'       => 201,
				'request_body' => '{"title":"legacy"}',
			),
			array(
				array( 'header', 'request', 'content_type', 'application/json' ),
				array( 'query', 'request', 'context', 'edit' ),
				array( 'header', 'response', 'X-Legacy', 'yes' ),
			)
		);

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->migrate_db_record( $log_id );

		$this->assertGreaterThan( 0, $post_id );
		$this->assertSame( (string) $log_id, get_post_meta( $post_id, '_wp_rest_api_log_migrated_id', true ) );

		// The direct date update bypasses the post cache.
		clean_post_cache( $post_id );

		$entry = new WP_REST_API_Log_Entry( $post_id );

		$this->assertSame( '/wp/v2/posts', $entry->route );
		$this->assertSame( '2016-05-01 10:20:30', $entry->time );
		$this->assertSame( '2016-05-01 10:20:30', $entry->time_gmt );
		$this->assertSame( 'POST', $entry->method );
		$this->assertSame( '201', $entry->status );
		$this->assertSame( '10.1.1.1', $entry->ip_address );
		$this->assertSame( 25, $entry->milliseconds );
		$this->assertSame( '{"title":"legacy"}', $entry->request->body );
		$this->assertSame( array( 'id' => 1 ), json_decode( $entry->response->body, true ) );
		$this->assertSame( 'application/json', $entry->request->headers['content_type'] );
		$this->assertSame( 'edit', $entry->request->query_params['context'] );
		$this->assertSame( 'yes', $entry->response->headers['X-Legacy'] );
	}

	/**
	 * Documents that a legacy meta row with an unknown type is stored under
	 * the type of the row before it.
	 *
	 * Bug: WP_REST_API_Log_DB::migrate_db_record() never resets $meta_type
	 * inside the loop, so a row whose meta_type is neither "header" nor
	 * "query" reuses the previous row's type instead of being skipped.
	 *
	 * @return void
	 */
	public function test_migrate_db_record_reuses_previous_meta_type() {

		$log_id = $this->insert_legacy_log(
			array(),
			array(
				array( 'query', 'request', 'context', 'view' ),
				array( 'unknown', 'request', 'mystery', 'value' ),
			)
		);

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->migrate_db_record( $log_id );

		$entry = new WP_REST_API_Log_Entry( $post_id );

		// Current behavior: the "unknown" row is stored as a query parameter.
		$this->assertSame( 'value', $entry->request->query_params['mystery'] );
	}

	/**
	 * Tests that only legacy rows that have not been migrated are returned.
	 *
	 * @return void
	 */
	public function test_get_log_ids_to_migrate() {

		$first  = $this->insert_legacy_log( array( 'route' => '/first' ) );
		$second = $this->insert_legacy_log( array( 'route' => '/second' ) );

		$db = new WP_REST_API_Log_DB();

		$this->assertEqualSets( array( (string) $first, (string) $second ), $db->get_log_ids_to_migrate() );

		$db->migrate_db_record( $first );

		$this->assertSame( array( (string) $second ), $db->get_log_ids_to_migrate() );
	}

	/**
	 * Tests that nothing needs migrating when the legacy tables do not exist.
	 *
	 * @return void
	 */
	public function test_get_log_ids_to_migrate_without_legacy_tables() {

		global $wpdb;

		// Stand in for a site without the legacy tables by pointing the
		// table lookup at a prefix that has no tables.
		$original_prefix = $wpdb->prefix;
		$wpdb->prefix    = 'no_such_prefix_';

		$db  = new WP_REST_API_Log_DB();
		$ids = $db->get_log_ids_to_migrate();

		$wpdb->prefix = $original_prefix;

		$this->assertSame( array(), $ids );
	}

	/**
	 * Tests the "wp rest-api-log migrate" command, run against stand-ins for
	 * the WP-CLI classes.
	 *
	 * @return void
	 */
	public function test_wp_cli_migrate() {

		require_once __DIR__ . '/stubs/wp-cli.php';
		require_once __DIR__ . '/stubs/wp-cli-utils.php';
		require_once WP_REST_API_LOG_PATH . 'includes/wp-cli/class-wp-rest-api-log-wp-cli-log.php';

		WP_CLI::$messages                        = array();
		WP_REST_API_Log_Test_Progress_Bar::$bars = array();

		$this->insert_legacy_log( array( 'route' => '/first' ) );
		$this->insert_legacy_log( array( 'route' => '/second' ) );

		$command = new WP_REST_API_Log_WP_CLI_Log();
		$command->migrate();

		$this->assertSame( array( 'success', 'Log entries migrated' ), end( WP_CLI::$messages ) );
		$this->assertSame( 'Migrating 2 entries:', WP_REST_API_Log_Test_Progress_Bar::$bars[0]->message );
		$this->assertSame( 2, WP_REST_API_Log_Test_Progress_Bar::$bars[0]->ticks );
		$this->assertCount( 2, WP_REST_API_Log_DB::get_all_log_ids() );

		// Running it again finds nothing left to migrate.
		$command->migrate();
		$this->assertSame( array( 'line', 'There are no more log entries that need to be migrated.' ), end( WP_CLI::$messages ) );
	}
}
