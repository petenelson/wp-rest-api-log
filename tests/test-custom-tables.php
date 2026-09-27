<?php
/**
 * Class WP_REST_API_Log_Test_Custom_Tables
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for storing log entries in custom database tables.
 */
class WP_REST_API_Log_Test_Custom_Tables extends WP_UnitTestCase {

	/**
	 * Whether the Advanced settings have been registered.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Turns on the custom tables setting.
	 *
	 * @return void
	 */
	public function enable_custom_tables() {
		update_option(
			'wp-rest-api-log-settings-advanced',
			array(
				'use-custom-tables' => '1',
			)
		);
	}

	/**
	 * Turns off the custom tables setting.
	 *
	 * @return void
	 */
	public function disable_custom_tables() {
		update_option(
			'wp-rest-api-log-settings-advanced',
			array(
				'use-custom-tables' => '0',
			)
		);
	}

	/**
	 * Registers the Advanced settings once per test case instance.
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( ! $this->registered ) {
			WP_REST_API_Log_Settings_Advanced::register_advanced_settings();
			$this->registered = true;
		}
	}

	/**
	 * Registers the Advanced settings before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->register_settings();
	}

	/**
	 * Switches back to the default tables after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Always return to the default tables, even when a test fails
		// part way through, so later tests don't run on the custom tables.
		WP_REST_API_Log_DB::switch_to_default_tables();
		parent::tear_down();
	}

	/**
	 * Tests the default custom table prefix.
	 *
	 * @return void
	 */
	public function test_get_custom_table_prefix() {
		$this->assertSame( 'rest_api_log_', WP_REST_API_Log_DB::get_custom_table_prefix() );
	}

	/**
	 * Tests that the custom tables setting can be turned on and off.
	 *
	 * @return void
	 */
	public function test_use_custom_tables() {

		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );

		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		$this->disable_custom_tables();
		$this->assertFalse( WP_REST_API_Log_DB::use_custom_tables() );
	}

	/**
	 * Tests switching $wpdb to the custom tables and back, and that the
	 * tables are created.
	 *
	 * @return void
	 */
	public function test_switch_custom_tables() {
		global $wpdb;

		$default_tables = array(
			'posts'   => $wpdb->posts,
			'options' => $wpdb->options,
			'users'   => $wpdb->users,
		);
		$custom_tables  = WP_REST_API_Log_DB::get_custom_table_names();

		$this->assertSame( $wpdb->prefix . 'rest_api_log_posts', $custom_tables['posts'] );
		$this->assertSame( $wpdb->prefix . 'rest_api_log_term_relationships', $custom_tables['term_relationships'] );

		// Make sure custom tables are turned off.
		$this->disable_custom_tables();

		// Try switching to custom tables, it should not switch.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );
		$this->assertSame( $default_tables['posts'], $wpdb->posts );

		// Turn on custom tables.
		$this->enable_custom_tables();
		$this->assertTrue( WP_REST_API_Log_DB::use_custom_tables() );

		// Switch to custom tables.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		// Only the post, term and meta tables are switched.
		$this->assertTrue( WP_REST_API_Log_DB::$using_custom_tables );
		foreach ( $custom_tables as $property => $table_name ) {
			$this->assertSame( $table_name, $wpdb->$property, $property );
		}
		$this->assertSame( $default_tables['options'], $wpdb->options );
		$this->assertSame( $default_tables['users'], $wpdb->users );

		// Verify the tables were created with the right structure. The test
		// suite creates them as temporary tables, which SHOW TABLES doesn't
		// list, but DESCRIBE works on them.
		$expected_columns = array(
			'posts'              => 'post_title',
			'postmeta'           => 'meta_key',
			'terms'              => 'slug',
			'termmeta'           => 'meta_key',
			'term_taxonomy'      => 'taxonomy',
			'term_relationships' => 'term_taxonomy_id',
		);
		foreach ( $custom_tables as $property => $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks the table's structure; there is no API for this.
			$columns = $wpdb->get_col( $wpdb->prepare( 'DESCRIBE %i', $table_name ) );
			$this->assertContains( $expected_columns[ $property ], $columns, $table_name );
		}

		// Switch back to default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();

		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );
		$this->assertSame( $default_tables['posts'], $wpdb->posts );
		$this->assertSame( $default_tables['options'], $wpdb->options );
	}

	/**
	 * Tests that posts and log entries are stored in, and read from, the
	 * tables that are active when the custom tables setting changes.
	 *
	 * @return void
	 */
	public function test_db_table_inserts() {

		global $wpdb;

		$default_prefix = $wpdb->prefix;
		$custom_prefix  = $default_prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

		// Make sure we're on the default tables. Note: when enabling/disabling,
		// be sure it's done on the default tables.
		WP_REST_API_Log_DB::switch_to_default_tables();
		$this->disable_custom_tables();

		$default_title   = 'Post in Default Tables ' . wp_generate_password( 6, false );
		$default_post_id = wp_insert_post(
			array(
				'post_title'  => $default_title,
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $default_post_id );

		// Switch to custom tables.
		$this->enable_custom_tables();
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$custom_title   = 'Post in Custom Tables ' . wp_generate_password( 6, false );
		$custom_post_id = wp_insert_post(
			array(
				'post_title'  => $custom_title,
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $custom_post_id );

		// Since we're on custom tables, we should not be able to find
		// the default post.
		$query_args = array(
			'post_type' => 'post',
			'title'     => $default_title,
		);

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
		$args               = array(
			'route'      => $default_route_name,
			'ip_address' => '192.168.1.1',
		);

		$db      = new \WP_REST_API_Log_DB();
		$post_id = $db->insert( $args );

		$this->assertGreaterThan( 0, $post_id );

		// Run a query to verify the inserted log record.
		$query_args = array(
			'post_type' => $post_type,
			'title'     => $default_route_name,
		);

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
		$args              = array(
			'route'      => $custom_route_name,
			'ip_address' => '192.168.100.50',
			'method'     => 'POST',
		);

		$db             = new \WP_REST_API_Log_DB();
		$custom_post_id = $db->insert( $args );

		$this->assertGreaterThan( 0, $custom_post_id );

		// Switch to custom tables since the plugin switches back after an insert.
		WP_REST_API_Log_DB::switch_to_custom_tables();

		// Run a query to verify the inserted log record.
		$query_args = array(
			'post_type' => $post_type,
			'title'     => $custom_route_name,
		);

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
		$query_args = array(
			'post_type' => $post_type,
			'title'     => $custom_route_name,
		);

		$query = new \WP_Query( $query_args );

		$this->assertEmpty( $query->posts );

		// Also check trying to get a custom entry.
		$custom_entry = new \WP_REST_API_Log_Entry( $custom_post_id );

		$this->assertNotSame( $custom_route_name, $custom_entry->route, $custom_route_name . '|' . $custom_entry->route );
	}

	/**
	 * Tests that log entries whose IDs overlap the site's posts and terms
	 * don't overwrite, or read from, the site's cached posts and terms.
	 *
	 * @return void
	 */
	public function test_overlapping_ids_do_not_share_cache() {
		global $wpdb;

		// A site post, loaded into the cache.
		$post_id = self::factory()->post->create( array( 'post_title' => 'Site post' ) );
		update_post_meta( $post_id, 'color', 'blue' );
		get_post( $post_id );
		get_post_meta( $post_id );

		$this->enable_custom_tables();

		// Give the log entry the same ID as the site post.
		$same_id = static function ( $new_post ) use ( $post_id ) {
			$new_post['import_id'] = $post_id;
			return $new_post;
		};
		add_filter( 'wp-rest-api-log-entries-pre-insert-new-post', $same_id );

		$db     = new WP_REST_API_Log_DB();
		$log_id = $db->insert(
			array(
				'route'  => '/custom/overlap',
				'method' => 'POST',
				'status' => 201,
			)
		);

		remove_filter( 'wp-rest-api-log-entries-pre-insert-new-post', $same_id );
		$this->assertSame( $post_id, $log_id );

		// Find the log's POST method term, then make sure a site term has the
		// same ID and is loaded into the cache.
		WP_REST_API_Log_DB::switch_to_custom_tables();
		$method_terms = wp_get_object_terms( $log_id, WP_REST_API_Log_DB::TAXONOMY_METHOD );
		WP_REST_API_Log_DB::switch_to_default_tables();

		$this->assertSame( 'POST', $method_terms[0]->name );
		$term_id = $method_terms[0]->term_id;

		if ( null === get_term( $term_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Creates a site term with a specific ID, which wp_insert_term() can't do.
			$wpdb->insert(
				$wpdb->terms,
				array(
					'term_id' => $term_id,
					'name'    => 'Site term',
					'slug'    => 'site-term-' . $term_id,
				)
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Pairs with the term row above.
			$wpdb->insert(
				$wpdb->term_taxonomy,
				array(
					'term_id'  => $term_id,
					'taxonomy' => 'category',
				)
			);
			clean_term_cache( $term_id, 'category' );
		}

		$site_term = get_term( $term_id );
		$this->assertInstanceOf( 'WP_Term', $site_term );
		$this->assertNotSame( WP_REST_API_Log_DB::TAXONOMY_METHOD, $site_term->taxonomy );

		// The site's post and meta are unchanged by the log insert.
		$post = get_post( $post_id );
		$this->assertSame( 'Site post', $post->post_title );
		$this->assertSame( 'post', $post->post_type );
		$this->assertSame( 'blue', get_post_meta( $post_id, 'color', true ) );

		// The log entry with the same ID has its own route and terms, not the
		// site's cached post and term.
		$entry = new WP_REST_API_Log_Entry( $log_id );
		$this->assertSame( '/custom/overlap', $entry->route );
		$this->assertSame( 'POST', $entry->method );
		$this->assertSame( '201', $entry->status );

		// Read directly while switched: the same IDs return log data.
		WP_REST_API_Log_DB::switch_to_custom_tables();
		$log_post = get_post( $post_id );
		$log_term = get_term( $term_id );
		WP_REST_API_Log_DB::switch_to_default_tables();

		$this->assertSame( WP_REST_API_Log_DB::POST_TYPE, $log_post->post_type );
		$this->assertSame( WP_REST_API_Log_DB::TAXONOMY_METHOD, $log_term->taxonomy );

		// And the site's post and term are still correct afterward.
		$this->assertSame( 'Site post', get_post( $post_id )->post_title );
		$this->assertSame( $site_term->name, get_term( $term_id )->name );
	}

	/**
	 * Tests that the site's object cache is wrapped only while switched.
	 *
	 * @return void
	 */
	public function test_object_cache_is_wrapped_while_switched() {
		global $wp_object_cache;

		$site_cache = $wp_object_cache;

		$this->enable_custom_tables();
		WP_REST_API_Log_DB::switch_to_custom_tables();

		$this->assertInstanceOf( 'WP_REST_API_Log_Object_Cache', $wp_object_cache );
		$this->assertSame( $site_cache, $wp_object_cache->unwrap() );

		// Post data set while switched stays with the log tables, while
		// other groups, such as options, are shared with the site.
		wp_cache_set( 'shared-key', 'log post', 'posts' );
		wp_cache_set( 'shared-key', 'shared option', 'options' );

		WP_REST_API_Log_DB::switch_to_default_tables();

		$this->assertSame( $site_cache, $wp_object_cache );
		$this->assertFalse( wp_cache_get( 'shared-key', 'posts' ) );
		$this->assertSame( 'shared option', wp_cache_get( 'shared-key', 'options' ) );
	}

	/**
	 * Tests that a switch_to_blog() call made by other code during an insert
	 * doesn't send the rest of the insert to the site's own tables.
	 *
	 * @return void
	 */
	public function test_switch_to_blog_during_insert() {
		global $wpdb, $wp_object_cache;

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$site_cache    = $wp_object_cache;
		$other_blog_id = self::factory()->blog->create();
		$seen          = array();

		$this->enable_custom_tables();

		// Simulate another plugin that switches sites when a post is saved.
		$switch = static function () use ( $other_blog_id, &$seen ) {
			global $wpdb, $wp_object_cache;

			switch_to_blog( $other_blog_id );
			$seen['other_posts'] = $wpdb->posts;
			$seen['other_cache'] = $wp_object_cache;
			restore_current_blog();
		};
		add_action( 'save_post_' . WP_REST_API_Log_DB::POST_TYPE, $switch );

		$db     = new WP_REST_API_Log_DB();
		$log_id = $db->insert(
			array(
				'route'      => '/custom/switch-blog',
				'method'     => 'PUT',
				'ip_address' => '10.0.0.1',
			)
		);

		remove_action( 'save_post_' . WP_REST_API_Log_DB::POST_TYPE, $switch );

		// The other site saw its own tables and the site's real cache.
		switch_to_blog( $other_blog_id );
		$this->assertSame( $wpdb->posts, $seen['other_posts'] );
		restore_current_blog();
		$this->assertSame( $site_cache, $seen['other_cache'] );

		// The terms and meta written after the switch are in the custom tables.
		$entry = new WP_REST_API_Log_Entry( $log_id );
		$this->assertSame( '/custom/switch-blog', $entry->route );
		$this->assertSame( 'PUT', $entry->method );
		$this->assertSame( '10.0.0.1', $entry->ip_address );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks the site's own table directly.
		$site_meta = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $log_id, WP_REST_API_Log_DB::POST_META_IP_ADDRESS ) );
		$this->assertSame( '0', $site_meta );

		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );
		$this->assertSame( $site_cache, $wp_object_cache );
	}

	/**
	 * Tests that each site on a multisite network gets its own custom tables,
	 * including a second site switched to later in the same request.
	 *
	 * @return void
	 */
	public function test_custom_tables_per_site() {
		global $wpdb;

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$other_blog_id = self::factory()->blog->create();
		$db            = new WP_REST_API_Log_DB();

		$this->enable_custom_tables();
		$main_id = $db->insert( array( 'route' => '/custom/main-site' ) );
		$this->assertGreaterThan( 0, $main_id );

		switch_to_blog( $other_blog_id );

		$this->enable_custom_tables();
		$other_tables = WP_REST_API_Log_DB::get_custom_table_names();
		$other_id     = $db->insert( array( 'route' => '/custom/other-site' ) );
		$other_entry  = new WP_REST_API_Log_Entry( $other_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks the table's structure; there is no API for this.
		$columns = $wpdb->get_col( $wpdb->prepare( 'DESCRIBE %i', $other_tables['posts'] ) );

		restore_current_blog();

		$this->assertSame( $wpdb->get_blog_prefix( $other_blog_id ) . 'rest_api_log_posts', $other_tables['posts'] );
		$this->assertContains( 'post_title', $columns );
		$this->assertGreaterThan( 0, $other_id );
		$this->assertSame( '/custom/other-site', $other_entry->route );
	}

	/**
	 * Tests that an exception during an insert still switches back to the
	 * site's tables and object cache.
	 *
	 * @return void
	 */
	public function test_switches_back_after_exception() {
		global $wpdb, $wp_object_cache;

		$site_posts = $wpdb->posts;
		$site_cache = $wp_object_cache;

		$this->enable_custom_tables();

		$throw = static function () {
			throw new RuntimeException( 'Insert failed.' );
		};
		add_filter( 'wp_insert_post_data', $throw );

		$db = new WP_REST_API_Log_DB();
		try {
			$db->insert( array( 'route' => '/custom/exception' ) );
			$this->fail( 'Expected the insert to throw.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'Insert failed.', $e->getMessage() );
		}

		remove_filter( 'wp_insert_post_data', $throw );

		$this->assertFalse( WP_REST_API_Log_DB::$using_custom_tables );
		$this->assertSame( $site_posts, $wpdb->posts );
		$this->assertSame( $site_cache, $wp_object_cache );
	}
}
