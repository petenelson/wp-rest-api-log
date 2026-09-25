<?php
/**
 * Class WP_REST_API_Log_Test_Admin
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the admin screens, scripts and capabilities.
 */
class WP_REST_API_Log_Test_Admin extends WP_UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	protected static $subscriber_id;

	/**
	 * Creates the users shared by the tests and loads the admin APIs.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id      = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$subscriber_id = $factory->user->create( array( 'role' => 'subscriber' ) );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	/**
	 * Resets globals changed by the tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['current_screen'], $_SERVER['HTTPS'] );
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
		parent::tear_down();
	}

	/**
	 * Inserts a log entry.
	 *
	 * @return int Post ID.
	 */
	protected function insert_entry() {
		$db = new WP_REST_API_Log_DB();
		return $db->insert( array( 'route' => '/wp/v2/posts' ) );
	}

	/**
	 * Tests the hooks registered by the admin class.
	 *
	 * @return void
	 */
	public function test_hooks_are_registered() {
		$this->assertSame( 10, has_filter( 'post_type_link', array( 'WP_REST_API_Log_Admin', 'entry_permalink' ) ) );
		$this->assertSame( 10, has_filter( 'get_edit_post_link', array( 'WP_REST_API_Log_Admin', 'entry_permalink' ) ) );
		$this->assertSame( 10, has_action( 'admin_init', array( 'WP_REST_API_Log_Admin', 'register_scripts' ) ) );
		$this->assertSame( 11, has_action( 'admin_init', array( 'WP_REST_API_Log_Admin', 'localize_script_data' ) ) );
		$this->assertSame( 10, has_filter( 'user_has_cap', array( 'WP_REST_API_Log_Admin', 'add_admin_caps' ) ) );
		$this->assertSame( 10, has_filter( 'plugin_action_links_' . WP_REST_API_LOG_BASENAME, array( 'WP_REST_API_Log_Admin', 'plugin_action_links' ) ) );
		$this->assertSame( 10, has_action( 'wp-rest-api-log-entry-property-links', array( 'WP_REST_API_Log_Admin', 'display_entry_property_links' ) ) );
	}

	/**
	 * Tests the hidden entry viewer page and the renamed Tools menu item.
	 *
	 * @return void
	 */
	public function test_admin_menu() {

		global $submenu;

		wp_set_current_user( self::$admin_id );

		$submenu['tools.php'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test fixture.
			array( 'Available Tools', 'edit_posts', 'tools.php' ),
			array( 'REST API Log Entries', 'edit_wp-rest-api-logs', 'edit.php?post_type=wp-rest-api-log', 'REST API Log Entries' ),
		);

		WP_REST_API_Log_Admin::admin_menu();

		$this->assertSame( 'admin_page_wp-rest-api-log-view-entry', WP_REST_API_Log_Admin::$view_entry_hook );
		$this->assertSame( 10, has_action( 'load-admin_page_wp-rest-api-log-view-entry', array( 'WP_REST_API_Log_Admin', 'set_view_entry_title' ) ) );

		$this->assertSame( 'Available Tools', $submenu['tools.php'][0][0] );
		$this->assertSame( 'REST API Log', $submenu['tools.php'][1][0] );
		$this->assertSame( 'REST API Log', $submenu['tools.php'][1][3] );
	}

	/**
	 * Tests the entry viewer page title.
	 *
	 * @return void
	 */
	public function test_set_view_entry_title() {

		global $title;

		$original = $title;

		WP_REST_API_Log_Admin::set_view_entry_title();
		$this->assertSame( 'REST API Log Entry', $title );

		$title = $original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the global.
	}

	/**
	 * Tests registering and localizing the admin scripts and styles.
	 *
	 * @return void
	 */
	public function test_register_scripts_and_localize() {

		add_filter(
			'wp-rest-api-log-admin-highlight-js-version',
			function () {
				return '1.2.3';
			}
		);

		WP_REST_API_Log_Admin::register_scripts();
		WP_REST_API_Log_Admin::localize_script_data();

		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin', 'registered' ) );
		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin-clipboard-js', 'registered' ) );
		$this->assertTrue( wp_style_is( 'wp-rest-api-log-admin', 'registered' ) );
		$this->assertTrue( wp_style_is( 'wp-rest-api-log-admin-highlight-js', 'registered' ) );

		$this->assertSame(
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/1.2.3/highlight.min.js',
			wp_scripts()->registered['wp-rest-api-log-admin-highlight-js']->src
		);
		$this->assertSame( WP_REST_API_LOG_URL . 'dist/js/admin.js', wp_scripts()->registered['wp-rest-api-log-admin']->src );
		$this->assertSame( WP_REST_API_Log_Common::VERSION, wp_scripts()->registered['wp-rest-api-log-admin']->ver );

		$data = wp_scripts()->get_data( 'wp-rest-api-log-admin', 'data' );
		$this->assertStringContainsString( 'var WP_REST_API_Log_Admin_Data', $data );
		$this->assertStringContainsString( 'batch-purge-all', $data );
	}

	/**
	 * Tests that the localized endpoints use https on SSL requests.
	 *
	 * @return void
	 */
	public function test_localize_script_data_ssl() {

		$_SERVER['HTTPS'] = 'on';

		WP_REST_API_Log_Admin::register_scripts();
		WP_REST_API_Log_Admin::localize_script_data();

		$data = wp_scripts()->get_data( 'wp-rest-api-log-admin', 'data' );
		$this->assertStringContainsString( '"purge_entries":"https:', $data );
	}

	/**
	 * Tests enqueueing the scripts only on the plugin's screens.
	 *
	 * @return void
	 */
	public function test_maybe_enqueue_scripts() {

		WP_REST_API_Log_Admin::register_scripts();

		set_current_screen( 'dashboard' );
		WP_REST_API_Log_Admin::maybe_enqueue_scripts();
		$this->assertFalse( wp_script_is( 'wp-rest-api-log-admin', 'enqueued' ) );

		set_current_screen( 'edit-wp-rest-api-log' );
		WP_REST_API_Log_Admin::maybe_enqueue_scripts();

		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin-highlight-js', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin-clipboard-js', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-rest-api-log-admin', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'wp-rest-api-log-admin-highlight-js', 'enqueued' ) );
	}

	/**
	 * Tests that the view and edit links for an entry point to the admin
	 * entry viewer.
	 *
	 * @return void
	 */
	public function test_entry_permalink() {

		wp_set_current_user( self::$admin_id );

		$post_id  = $this->insert_entry();
		$expected = add_query_arg(
			array(
				'page' => 'wp-rest-api-log-view-entry',
				'id'   => $post_id,
			),
			admin_url( 'tools.php' )
		);

		$this->assertSame( $expected, get_permalink( $post_id ) );
		$this->assertSame( $expected, WP_REST_API_Log_Admin::entry_permalink( 'http://example.org/other', $post_id ) );

		// Other post types are left alone.
		$other_post = self::factory()->post->create();
		$this->assertSame( 'http://example.org/other', WP_REST_API_Log_Admin::entry_permalink( 'http://example.org/other', get_post( $other_post ) ) );
	}

	/**
	 * Tests that log entries are left out of the link inserter.
	 *
	 * @return void
	 */
	public function test_wp_link_query_args() {

		$query = WP_REST_API_Log_Admin::wp_link_query_args(
			array(
				'post_type' => array( 'post', 'wp-rest-api-log', 'page' ),
			)
		);

		$this->assertSame( array( 'post', 'page' ), array_values( $query['post_type'] ) );

		$query = array( 'post_type' => 'post' );
		$this->assertSame( $query, WP_REST_API_Log_Admin::wp_link_query_args( $query ) );
	}

	/**
	 * Tests that the admin title is passed through.
	 *
	 * @return void
	 */
	public function test_admin_title() {
		$this->assertSame( 'Title', WP_REST_API_Log_Admin::admin_title( 'Title' ) );
	}

	/**
	 * Tests that administrators get the log entry capabilities.
	 *
	 * @return void
	 */
	public function test_add_admin_caps() {

		$post_type = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

		$caps = array(
			$post_type->cap->edit_posts,
			$post_type->cap->delete_posts,
			$post_type->cap->read_post,
			$post_type->cap->edit_post,
			$post_type->cap->delete_post,
		);

		foreach ( $caps as $cap ) {
			$this->assertTrue( user_can( self::$admin_id, $cap ), $cap );
			$this->assertFalse( user_can( self::$subscriber_id, $cap ), $cap );
		}

		// The filter only changes the capabilities of administrators.
		$allcaps = array( 'read' => true );
		$this->assertSame( $allcaps, WP_REST_API_Log_Admin::add_admin_caps( $allcaps, array(), array( 'read', self::$subscriber_id ) ) );
		$this->assertSame( $allcaps, WP_REST_API_Log_Admin::add_admin_caps( $allcaps, array(), array( 'read', 0 ) ) );
	}

	/**
	 * Tests the Settings and Log links on the Plugins screen.
	 *
	 * @return void
	 */
	public function test_plugin_action_links() {

		wp_set_current_user( self::$admin_id );

		$actions = array( 'deactivate' => '<a href="#">Deactivate</a>' );

		// Nothing is added while the plugin is inactive.
		update_option( 'active_plugins', array() );
		$this->assertSame( $actions, WP_REST_API_Log_Admin::plugin_action_links( $actions, WP_REST_API_LOG_BASENAME, array(), 'all' ) );

		update_option( 'active_plugins', array( WP_REST_API_LOG_BASENAME ) );
		$links = WP_REST_API_Log_Admin::plugin_action_links( $actions, WP_REST_API_LOG_BASENAME, array(), 'all' );

		$this->assertSame( array( 'deactivate', 'settings', 'log' ), array_keys( $links ) );
		$this->assertStringContainsString( 'admin.php?page=wp-rest-api-log-settings', $links['settings'] );
		$this->assertStringContainsString( 'edit.php?post_type=wp-rest-api-log', $links['log'] );

		// Users without manage_options do not get the links.
		wp_set_current_user( self::$subscriber_id );
		$this->assertSame( $actions, WP_REST_API_Log_Admin::plugin_action_links( $actions, WP_REST_API_LOG_BASENAME, array(), 'all' ) );
	}

	/**
	 * Tests the download and copy links for an entry property.
	 *
	 * @return void
	 */
	public function test_display_entry_property_links() {

		ob_start();
		do_action(
			'wp-rest-api-log-entry-property-links',
			array(
				'rr'            => 'request',
				'property'      => 'headers',
				'download_urls' => array(
					'request' => array(
						'headers' => 'http://example.org/download?a=1&b=2',
					),
				),
			)
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( '<a href="http://example.org/download?a=1&#038;b=2">Download</a>', $html );
		$this->assertStringContainsString( 'data-clipboard-target="#wp-rest-api-log-entry .request-headers code"', $html );
	}

	/**
	 * Tests that the entry viewer template can be replaced, and that the
	 * scripts are enqueued after it is displayed.
	 *
	 * @return void
	 */
	public function test_display_log_entry_template_filter() {

		WP_REST_API_Log_Admin::register_scripts();

		add_filter(
			'wp-rest-api-log-admin-view-entry-template',
			function () {
				return __DIR__ . '/fixtures/view-entry-template.php';
			}
		);

		ob_start();
		WP_REST_API_Log_Admin::display_log_entry();
		$html = ob_get_clean();

		$this->assertSame( 'custom view entry template', $html );
		$this->assertTrue( wp_script_is( 'wp-rest-api-log-admin', 'enqueued' ) );
	}

	/**
	 * Tests that the entry viewer stops when no valid entry ID is given.
	 *
	 * The template reads the ID with filter_input( INPUT_GET ), which cannot
	 * be set from a test, so only this path of the template can be tested.
	 *
	 * @return void
	 */
	public function test_display_log_entry_without_an_id() {

		wp_set_current_user( self::$admin_id );

		$this->expectException( 'WPDieException' );
		$this->expectExceptionMessage( 'Invalid WP REST API Log Entry ID' );

		WP_REST_API_Log_Admin::display_log_entry();
	}
}
