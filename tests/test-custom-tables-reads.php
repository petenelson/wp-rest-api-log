<?php
/**
 * Class WP_REST_API_Log_Test_Custom_Tables_Reads
 *
 * @package wp-rest-api-log
 */

/**
 * Tests that log entries stored in the custom tables are found, shown and
 * deleted by the plugin's queries, REST endpoints, purges and admin screens,
 * and that the site's own posts with the same IDs are left alone.
 */
class WP_REST_API_Log_Test_Custom_Tables_Reads extends WP_UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Creates the administrator shared by the tests.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Turns on custom tables and sets up a fresh REST server.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'wp-rest-api-log-settings-advanced', array( 'use-custom-tables' => '1' ) );

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	/**
	 * Switches back to the site's tables and removes the REST server.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server, $typenow;

		WP_REST_API_Log_DB::switch_to_default_tables( true );
		$wp_rest_server = null;
		$typenow        = '';

		parent::tear_down();
	}

	/**
	 * Inserts a log entry into the custom tables.
	 *
	 * @param  string   $route Route.
	 * @param  string   $date  Optional. Post date, in site-local time.
	 * @param  int|null $id    Optional. ID to request for the entry.
	 * @return int Log entry ID.
	 */
	protected function insert_entry( $route, $date = '', $id = null ) {

		$set_post = static function ( $new_post ) use ( $date, $id ) {
			if ( ! empty( $date ) ) {
				$new_post['post_date']     = $date;
				$new_post['post_date_gmt'] = get_gmt_from_date( $date );
			}
			if ( ! empty( $id ) ) {
				$new_post['import_id'] = $id;
			}
			return $new_post;
		};
		add_filter( 'wp-rest-api-log-entries-pre-insert-new-post', $set_post );

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->insert(
			array(
				'route'  => $route,
				'method' => 'POST',
				'status' => 201,
			)
		);

		remove_filter( 'wp-rest-api-log-entries-pre-insert-new-post', $set_post );

		return $post_id;
	}

	/**
	 * Returns a date a number of days in the past, in site-local time.
	 *
	 * @param  int $days Number of days.
	 * @return string
	 */
	protected function days_ago( $days ) {
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date, WordPress.DateTime.CurrentTimeTimestamp.Requested -- Matches the site-local time used by the purge.
		return date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS ) );
	}

	/**
	 * Tests that searching and listing IDs use the custom tables.
	 *
	 * @return void
	 */
	public function test_search_and_ids_use_custom_tables() {

		// Dated in the past, since search() excludes entries from the current second.
		$log_id = $this->insert_entry( '/custom/search', $this->days_ago( 1 ) );

		$db    = new WP_REST_API_Log_DB();
		$posts = $db->search( array( 'route' => '/custom/search' ) );

		$this->assertCount( 1, $posts );
		$this->assertSame( $log_id, $posts[0]->ID );
		$this->assertSame( array( $log_id ), WP_REST_API_Log_DB::get_all_log_ids() );
		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );
	}

	/**
	 * Tests that the REST endpoints read from the custom tables, including an
	 * entry that shares its ID with a site post.
	 *
	 * @return void
	 */
	public function test_rest_endpoints_use_custom_tables() {

		$site_post_id = self::factory()->post->create( array( 'post_title' => 'Site post' ) );
		$log_id       = $this->insert_entry( '/custom/rest', $this->days_ago( 1 ), $site_post_id );
		$this->assertSame( $site_post_id, $log_id );

		wp_set_current_user( self::$admin_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp-rest-api-log/entries' ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( '/custom/rest' ), wp_list_pluck( $response->get_data(), 'route' ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp-rest-api-log/entry/' . $log_id ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '/custom/rest', $response->get_data()->route );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp-rest-api-log/routes' ) );
		$this->assertSame( array( '/custom/rest' ), $response->get_data() );

		// The site's post is untouched.
		$this->assertSame( 'Site post', get_post( $site_post_id )->post_title );
	}

	/**
	 * Tests that purging removes entries from the custom tables only.
	 *
	 * @return void
	 */
	public function test_purges_use_custom_tables() {

		$site_post_id = self::factory()->post->create( array( 'post_title' => 'Site post' ) );
		$old_id       = $this->insert_entry( '/custom/old', $this->days_ago( 10 ), $site_post_id );
		$recent_id    = $this->insert_entry( '/custom/recent', $this->days_ago( 1 ) );

		$this->assertSame( $site_post_id, $old_id );

		// A dry run counts the old entry without deleting it.
		$this->assertSame( 1, WP_REST_API_Log::purge_old_records( 5, true ) );
		$this->assertCount( 2, WP_REST_API_Log_DB::get_all_log_ids() );

		$this->assertSame( 1, WP_REST_API_Log::purge_old_records( 5 ) );
		$this->assertSame( array( $recent_id ), WP_REST_API_Log_DB::get_all_log_ids() );

		// The site's post with the purged entry's ID is still there.
		$this->assertSame( 'Site post', get_post( $site_post_id )->post_title );

		// The method term's count in the custom tables was updated.
		WP_REST_API_Log_DB::switch_to_custom_tables();
		$post_term = get_term_by( 'name', 'POST', WP_REST_API_Log_DB::TAXONOMY_METHOD );
		WP_REST_API_Log_DB::switch_to_default_tables();
		$this->assertSame( 1, $post_term->count );

		WP_REST_API_Log_DB::purge_all_log_entries();
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
		$this->assertSame( 'Site post', get_post( $site_post_id )->post_title );
	}

	/**
	 * Tests that the batch purge endpoint deletes from the custom tables.
	 *
	 * @return void
	 */
	public function test_batch_purge_uses_custom_tables() {

		$this->insert_entry( '/custom/one' );
		$this->insert_entry( '/custom/two' );
		$this->assertCount( 2, WP_REST_API_Log_DB::get_all_log_ids() );

		wp_set_current_user( self::$admin_id );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'DELETE', '/wp-rest-api-log/batch-purge-all' ) );

		$this->assertSame( 0, $response->get_data()['entries_left'] );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests that the log list screen and log entry actions on post.php switch
	 * to the custom tables for the rest of the request, and other screens
	 * don't.
	 *
	 * @return void
	 */
	public function test_log_screens_switch_tables() {
		global $typenow;

		$list_table = new WP_REST_API_Log_Admin_List_Table();

		$typenow = 'post';
		$list_table->switch_tables_for_log_screen();
		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );

		$typenow = WP_REST_API_Log_DB::POST_TYPE;
		$list_table->switch_tables_for_log_screen();
		$this->assertTrue( WP_REST_API_Log_DB::$using_custom_tables );
		$this->assertSame( 0, has_action( 'shutdown', array( 'WP_REST_API_Log_DB', 'switch_to_default_tables' ) ) );

		remove_action( 'shutdown', array( 'WP_REST_API_Log_DB', 'switch_to_default_tables' ), 0 );
	}

	/**
	 * Tests that trash, restore and delete links for log entries include the
	 * post type, so post.php can switch tables before loading the entry.
	 *
	 * @return void
	 */
	public function test_entry_action_links_include_post_type() {

		$log_id = $this->insert_entry( '/custom/links' );

		wp_set_current_user( self::$admin_id );

		WP_REST_API_Log_DB::switch_to_custom_tables();
		$delete_link = get_delete_post_link( $log_id, '', true );
		$trash_link  = get_delete_post_link( $log_id );
		WP_REST_API_Log_DB::switch_to_default_tables();

		$this->assertStringContainsString( 'post_type=' . WP_REST_API_Log_DB::POST_TYPE, $delete_link );
		$this->assertStringContainsString( 'post_type=' . WP_REST_API_Log_DB::POST_TYPE, $trash_link );
	}
}
