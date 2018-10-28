<?php
/**
 * Class WP_REST_API_Log_Test_DB
 *
 * @package 
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_DB extends WP_UnitTestCase {

	private $_registered = false;

	public function enable_custom_tables() {
		update_option( 'wp-rest-api-log-settings-advanced', [
			'use-custom-tables' => '1',
		] );
	}

	public function disable_custom_tables() {
		update_option( 'wp-rest-api-log-settings-advanced', [
			'use-custom-tables' => '0',
		] );
	}

	public function register_settings() {
		if ( ! $this->_registered ) {
			WP_REST_API_Log_Settings_Advanced::register_advanced_settings();
			$this->_registered = true;
		}
	}

	public function setUp() {
		$this->register_settings();
	}

	public function test_get_custom_table_prefix() {
		$this->assertSame( 'rest_api_log_', WP_REST_API_Log_DB::get_custom_table_prefix() );
	}

	public function test_use_custom_tables() {

		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );

		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		$this->disable_custom_tables();
		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );
	}

	public function test_switch_custom_tables() {
		global $wpdb;

		$default_prefix = $wpdb->prefix;
		$custom_prefix = $default_prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

		// Make sure custom tables are turned off.
		$this->disable_custom_tables();

		// Try switching to custom tables, it should not switch.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$this->assertSame( $default_prefix, $wpdb->prefix );

		// Turn on custom tables.
		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		// Switch to custom tables.		WP_REST_API_Log_DB::switch_to_custom_tables();

		// Verify wpdb is using the custom prefix.
		$this->assertSame( $custom_prefix, $wpdb->prefix );

		// Verify the tables were created.
		foreach ( WP_REST_API_Log_DB::get_custom_table_names() as $table_name ) {
			$sql = $wpdb->prepare( "SHOW TABLES LIKE '%s';", $table_name );
			$results = $wpdb->get_row( $sql );

			$this->assertTrue( ! is_wp_error( $results ) );
			$this->assertNotEmpty( $results );
		}

		// Switch back to default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();

		// Verify wpdb is using the default prefix.
		$this->assertSame( $default_prefix, $wpdb->prefix );
	}

	public function test_db_table_inserts() {

		global $wpdb;

		$default_prefix = $wpdb->prefix;
		$custom_prefix = $default_prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

		// Make sure we're on the default tables. Note: when enabling/disabling,
		// be sure it's done on the default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();
		$this->disable_custom_tables();

		$default_title = 'Post in Default Tables ' . wp_generate_password( 6, false );
		$default_post_id = wp_insert_post( [ 'post_title' => $default_title, 'post_status' => 'publish' ] );

		$this->assertGreaterThan( 0, $default_post_id );

		// Switch to custom tables.
		$this->enable_custom_tables();
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$custom_title = 'Post in Custom Tables ' . wp_generate_password( 6, false );
		$custom_post_id = wp_insert_post( [ 'post_title' => $custom_title, 'post_status' => 'publish' ] );

		$this->assertGreaterThan( 0, $custom_post_id );

		// Since we're on custom tables, we should not be able to find
		// the default post.
		$query_args = [
			'post_type' => 'post',
			'title' => $default_title,
		];

		$query = new \WP_Query( $query_args );

		$this->assertEmpty( $query->posts );

		// Verify the post in the custom table.
		$query_args['title'] = $custom_title;

		$query = new \WP_Query( $query_args );

		$this->assertNotEmpty( $query->posts );
		$this->assertSame( $custom_post_id, $query->posts[0]->ID );

		// Switch back to default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();
		$this->disable_custom_tables();

		// Since we're on default tables, we should not be able to find
		// the custom post.
		$query_args['title'] = $custom_title;

		$query = new \WP_Query( $query_args );

		$this->assertEmpty( $query->posts );

		// Verify the post in the default table.
		$query_args['title'] = $default_title;

		$query = new \WP_Query( $query_args );

		$this->assertNotEmpty( $query->posts );
		$this->assertSame( $default_title, $query->posts[0]->post_title );
		$this->assertSame( $default_post_id, $query->posts[0]->ID );

		wp_delete_post( $default_post_id, true );

		// Cool, now that we've tested basic insert, use the plugin to
		// save a log record.
		$post_type = WP_REST_API_Log_DB::POST_TYPE;

		$default_route_name = 'default/route-' . wp_generate_password( 10, false );
		$args = [
			'route' => $default_route_name,
			'ip_address' => '192.168.1.1',
		];

		$db = new \WP_REST_API_Log_DB();
		$post_id = $db->insert( $args );

		$this->assertGreaterThan( 0, $post_id );

		// Run a query to verify the inserted log record.
		$query_args = [
			'post_type' => $post_type,
			'title' => $default_route_name,
		];

		$query = new \WP_Query( $query_args );

		$this->assertNotEmpty( $query->posts );
		$this->assertSame( $post_id, $query->posts[0]->ID );

		$entry = new \WP_REST_API_Log_Entry( $post_id );

		// Verify the entry, terms and meta.
		$this->assertSame( $post_id, $entry->ID );
		$this->assertSame( '192.168.1.1', $entry->ip_address );
		$this->assertSame( 'GET', $entry->method );

		// Enable custom tables. The plugin will do the table switching
		// automatically.
		$this->enable_custom_tables();

		$custom_route_name = 'custom/route-' . wp_generate_password( 10, false );
		$args = [
			'route' => $custom_route_name,
			'ip_address' => '192.168.100.50',
			'method' => 'POST',
		];

		$db = new \WP_REST_API_Log_DB();
		$custom_post_id = $db->insert( $args );

		$this->assertGreaterThan( 0, $custom_post_id );

		// Switch to custom tables since the plugin switches back after an insert.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		// Run a query to verify the inserted log record.
		$query_args = [
			'post_type' => $post_type,
			'title' => $custom_route_name,
		];

		$query = new \WP_Query( $query_args );

		$this->assertNotEmpty( $query->posts );
		$this->assertSame( $custom_post_id, $query->posts[0]->ID );

		// Switch back to default tables. The plugin will do the switching.
		WP_REST_API_Log_DB::switch_to_default_tables();

		$entry = new \WP_REST_API_Log_Entry( $custom_post_id );

		// Verify the entry, terms and meta.
		$this->assertSame( $custom_post_id, $entry->ID );
		$this->assertSame( '192.168.100.50', $entry->ip_address );
		$this->assertSame( 'POST', $entry->method );

		// Disable custom tables and try getting the custom entry, should
		// fail since we're not on the custom tables at this point.
		$this->disable_custom_tables();

		// Run a query to verify the inserted log record is not available
		// in the default tables.
		$query_args = [
			'post_type' => $post_type,
			'title' => $custom_route_name,
		];

		$query = new \WP_Query( $query );

		$this->assertEmpty( $query->posts );

		// Also check trying to get a custom entry.
		$custom_entry = new \WP_REST_API_Log_Entry( $custom_post_id );

		$this->assertNotSame( $custom_route_name, $custom_entry->route, $custom_route_name . '|' . $custom_entry->route );
	}
}
