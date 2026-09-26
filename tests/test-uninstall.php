<?php
/**
 * Class WP_REST_API_Log_Test_Uninstall
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for removing the plugin's data when it is uninstalled.
 */
class WP_REST_API_Log_Test_Uninstall extends WP_UnitTestCase {

	/**
	 * Keeps the uninstall script's DROP TABLE statements on temporary tables.
	 *
	 * The test suite only rewrites upper case "DROP TABLE" statements, while
	 * uninstall.php uses lower case, so a real table would be dropped and
	 * the test's transaction committed.
	 *
	 * @param  string $query SQL query.
	 * @return string
	 */
	public function drop_temporary_tables( $query ) {
		if ( 0 === stripos( trim( $query ), 'drop table' ) ) {
			return 'DROP TEMPORARY TABLE' . substr( trim( $query ), 10 );
		}
		return $query;
	}

	/**
	 * Tests that the legacy tables and the plugin's options are removed.
	 *
	 * @return void
	 */
	public function test_uninstall() {

		global $wpdb;

		// Created as temporary tables by the test suite.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test fixture tables.
		$wpdb->query( "CREATE TABLE {$wpdb->prefix}wp_rest_api_log ( id int )" );
		$wpdb->query( "CREATE TABLE {$wpdb->prefix}wp_rest_api_logmeta ( id int )" );
		// phpcs:enable

		$options = array(
			'wp-rest-api-log-meta-dbversion',
			'wp-rest-api-log-entries-dbversion',
			'wp-rest-api-log-settings-general',
			'wp-rest-api-log-settings-routes',
			'wp-rest-api-log-settings-headers',
			'wp-rest-api-log-settings-elasticpress',
			'wp-rest-api-log-plugin-activated',
			'wp-rest-api-log-db-notice-dismissed',
		);

		foreach ( $options as $option ) {
			update_option( $option, '1' );
		}

		update_option( 'blogname', 'Kept' );

		add_filter( 'query', array( $this, 'drop_temporary_tables' ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', WP_REST_API_LOG_BASENAME );
		}

		include WP_REST_API_LOG_PATH . 'uninstall.php';

		remove_filter( 'query', array( $this, 'drop_temporary_tables' ) );

		foreach ( $options as $option ) {
			$this->assertFalse( get_option( $option ), $option );
		}

		$this->assertSame( 'Kept', get_option( 'blogname' ) );

		// Selecting from a dropped table fails.
		$suppress = $wpdb->suppress_errors( true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Test fixture table.
		$result = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wp_rest_api_log" );
		$wpdb->suppress_errors( $suppress );

		$this->assertNull( $result );
		$this->assertStringContainsString( "doesn't exist", $wpdb->last_error );
	}

	/**
	 * Documents that uninstalling leaves the log entries and the purge cron
	 * event behind.
	 *
	 * Bug: uninstall.php only removes the legacy tables and the options.
	 * Log entries are stored as posts, with their request and response data
	 * in post meta, and none of them are deleted. The hourly
	 * "wp-rest-api-log-purge-old-records" event also stays scheduled.
	 *
	 * @return void
	 */
	public function test_uninstall_leaves_log_entries() {

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->insert( array( 'route' => '/wp/v2/users/me' ) );

		wp_clear_scheduled_hook( 'wp-rest-api-log-purge-old-records' );
		WP_REST_API_Log::create_purge_cron();

		add_filter( 'query', array( $this, 'drop_temporary_tables' ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', WP_REST_API_LOG_BASENAME );
		}

		include WP_REST_API_LOG_PATH . 'uninstall.php';

		remove_filter( 'query', array( $this, 'drop_temporary_tables' ) );

		$scheduled = wp_next_scheduled( 'wp-rest-api-log-purge-old-records' );
		wp_clear_scheduled_hook( 'wp-rest-api-log-purge-old-records' );

		// Current behavior: the entry, its meta and the cron event remain.
		$this->assertInstanceOf( 'WP_Post', get_post( $post_id ) );
		$this->assertNotEmpty( get_post_meta( $post_id ) );
		$this->assertNotFalse( $scheduled );
	}
}
