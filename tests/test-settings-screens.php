<?php
/**
 * Class WP_REST_API_Log_Test_Settings_Screens
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the settings page, its tabs and the related admin notices.
 */
class WP_REST_API_Log_Test_Settings_Screens extends WP_UnitTestCase {

	use WP_REST_API_Log_Test_Hooks;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Creates the users shared by the tests and loads the admin APIs.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( array( 'role' => 'administrator' ) );

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
		$_GET = array();
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Captures the output of a callable.
	 *
	 * @param  callable $callback Callback that prints output.
	 * @return string
	 */
	protected function capture( $callback ) {
		ob_start();
		call_user_func( $callback );
		return ob_get_clean();
	}

	/**
	 * Tests the hooks registered by each settings class.
	 *
	 * @return void
	 */
	public function test_hooks_are_registered() {

		$this->assert_registers_hooks(
			array( 'WP_REST_API_Log_Settings', 'plugins_loaded' ),
			array(
				array( 'admin_menu', array( 'WP_REST_API_Log_Settings', 'admin_menu' ), 10 ),
				array( 'admin_notices', array( 'WP_REST_API_Log_Settings', 'activation_admin_notice' ), 10 ),
				array( 'wp-rest-api-log-setting-is-enabled', array( 'WP_REST_API_Log_Settings', 'filter_setting_is_enabled' ), 10 ),
				array( 'wp-rest-api-log-setting-get', array( 'WP_REST_API_Log_Settings', 'setting_get' ), 10 ),
			)
		);

		$this->assert_registers_hooks(
			array( 'WP_REST_API_Log_Settings_General', 'plugins_loaded' ),
			array(
				array( 'admin_init', array( 'WP_REST_API_Log_Settings_General', 'register_general_settings' ), 10 ),
				array( 'wp-rest-api-log-settings-tabs', array( 'WP_REST_API_Log_Settings_General', 'add_tab' ), 10 ),
				array( 'admin_notices', array( 'WP_REST_API_Log_Settings_General', 'display_db_notice' ), 10 ),
				array( 'wp_ajax_wp-rest-api-log-db-notice-dismiss', array( 'WP_REST_API_Log_Settings_General', 'dismiss_db_notice' ), 10 ),
			)
		);

		$tabs = array(
			'WP_REST_API_Log_Settings_Routes'       => 'register_routes_settings',
			'WP_REST_API_Log_Settings_Headers'      => 'register_headers_settings',
			'WP_REST_API_Log_Settings_ElasticPress' => 'register_elasticpress_settings',
			'WP_REST_API_Log_Settings_Help'         => 'register_help_settings',
		);

		foreach ( $tabs as $class_name => $register_method ) {
			$this->assert_registers_hooks(
				array( $class_name, 'plugins_loaded' ),
				array(
					array( 'admin_init', array( $class_name, $register_method ), 10 ),
					array( 'wp-rest-api-log-settings-tabs', array( $class_name, 'add_tab' ), 10 ),
				)
			);
		}
	}

	/**
	 * Tests that every tab is added, in order.
	 *
	 * @return void
	 */
	public function test_settings_tabs() {

		$tabs = apply_filters( 'wp-rest-api-log-settings-tabs', array() );

		$this->assertSame(
			array(
				'wp-rest-api-log-settings-general'      => 'General',
				'wp-rest-api-log-settings-routes'       => 'Routes',
				'wp-rest-api-log-settings-headers'      => 'Headers',
				'wp-rest-api-log-settings-elasticpress' => 'ElasticPress',
				'wp-rest-api-log-settings-help'         => 'Help',
			),
			$tabs
		);
	}

	/**
	 * Tests that each tab registers its setting, section and fields.
	 *
	 * @return void
	 */
	public function test_register_settings() {

		global $wp_registered_settings, $wp_settings_sections, $wp_settings_fields;

		WP_REST_API_Log_Settings_General::register_general_settings();
		WP_REST_API_Log_Settings_Routes::register_routes_settings();
		WP_REST_API_Log_Settings_Headers::register_headers_settings();
		WP_REST_API_Log_Settings_ElasticPress::register_elasticpress_settings();
		WP_REST_API_Log_Settings_Help::register_help_settings();

		$expected_fields = array(
			'wp-rest-api-log-settings-general'      => array( 'general', array( 'logging-enabled', 'purge-days', 'ip-address-display' ) ),
			'wp-rest-api-log-settings-routes'       => array( 'routes', array( 'ignore-core-oembed', 'route-log-matching-mode', 'route-filters' ) ),
			'wp-rest-api-log-settings-headers'      => array( 'headers', array( 'redacted-request-headers', 'redacted-response-headers' ) ),
			'wp-rest-api-log-settings-elasticpress' => array( 'elasticpress', array( 'logging-enabled' ) ),
		);

		foreach ( $expected_fields as $key => $expected ) {
			list( $section, $fields ) = $expected;

			$this->assertArrayHasKey( $key, $wp_registered_settings, $key );
			$this->assertArrayHasKey( $section, $wp_settings_sections[ $key ], $key );
			$this->assertSame( $fields, array_keys( $wp_settings_fields[ $key ][ $section ] ), $key );
		}

		// The Help tab only has a section.
		$this->assertArrayHasKey( 'help', $wp_settings_sections['wp-rest-api-log-settings-help'] );

		// The header textareas default to the redacted header lists.
		$this->assertSame(
			WP_REST_API_Log_Settings_Headers::get_default_settings()['redacted-request-headers'],
			$wp_settings_fields['wp-rest-api-log-settings-headers']['headers']['redacted-request-headers']['args']['default']
		);
	}

	/**
	 * Tests sanitizing the General tab.
	 *
	 * @return void
	 */
	public function test_sanitize_general_settings() {

		$this->assertSame( array( 'purge-days' => 30 ), WP_REST_API_Log_Settings_General::sanitize_settings( array( 'purge-days' => '30' ) ) );
		$this->assertSame( array( 'purge-days' => 5 ), WP_REST_API_Log_Settings_General::sanitize_settings( array( 'purge-days' => '-5' ) ) );
		$this->assertSame( array( 'purge-days' => '' ), WP_REST_API_Log_Settings_General::sanitize_settings( array( 'purge-days' => '0' ) ) );
		$this->assertSame( array( 'purge-days' => '' ), WP_REST_API_Log_Settings_General::sanitize_settings( array( 'purge-days' => 'abc' ) ) );
		$this->assertSame( array( 'purge-days' => '' ), WP_REST_API_Log_Settings_General::sanitize_settings( array() ) );
	}

	/**
	 * Tests sanitizing the Routes tab.
	 *
	 * @return void
	 */
	public function test_sanitize_routes_settings() {

		$settings = WP_REST_API_Log_Settings_Routes::sanitize_settings(
			array(
				'ignore-core-oembed'      => ' 1<b> ',
				'route-log-matching-mode' => 'log_matches',
				'route-filters'           => "/wp/v2/*\n^\/wp\/v2\/posts$",
			)
		);

		$this->assertSame( '1', $settings['ignore-core-oembed'] );
		$this->assertSame( 'log_matches', $settings['route-log-matching-mode'] );

		// Route filters are kept as-is so line breaks and regex survive.
		$this->assertSame( "/wp/v2/*\n^\/wp\/v2\/posts$", $settings['route-filters'] );

		$this->assertSame( array(), WP_REST_API_Log_Settings_Routes::sanitize_settings( array() ) );
	}

	/**
	 * Tests sanitizing the Headers tab one line at a time.
	 *
	 * @return void
	 */
	public function test_sanitize_headers_settings() {

		$settings = WP_REST_API_Log_Settings_Headers::sanitize_settings(
			array(
				'redacted-request-headers'  => "Authorization\r\n\n  <b>Cookie</b>  \nX-Api-Key",
				'redacted-response-headers' => '',
			)
		);

		$this->assertSame( "Authorization\nCookie\nX-Api-Key", $settings['redacted-request-headers'] );
		$this->assertSame( '', $settings['redacted-response-headers'] );

		$this->assertSame( array( 'other' => 'x' ), WP_REST_API_Log_Settings_Headers::sanitize_settings( array( 'other' => 'x' ) ) );
	}

	/**
	 * Tests the ElasticPress tab defaults and sanitizer.
	 *
	 * @return void
	 */
	public function test_elasticpress_settings() {

		$this->assertSame( array( 'logging-enabled' => '1' ), WP_REST_API_Log_Settings_ElasticPress::get_default_settings() );
		$this->assertSame( array( 'logging-enabled' => '0' ), WP_REST_API_Log_Settings_ElasticPress::sanitize_settings( array( 'logging-enabled' => '0' ) ) );
	}

	/**
	 * Tests the section descriptions for the Headers and Help tabs.
	 *
	 * @return void
	 */
	public function test_section_headers() {

		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Headers', 'section_header' ) );
		$this->assertStringContainsString( 'One header name per line.', $html );
		$this->assertStringContainsString( '<code>[REDACTED]</code>', $html );

		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Help', 'section_header' ) );
		$this->assertStringContainsString( 'class="wp-rest-api-log-help"', $html );
		$this->assertStringContainsString( 'https://github.com/petenelson/wp-rest-api-log', $html );
	}

	/**
	 * Tests the notice shown after the plugin is activated.
	 *
	 * @return void
	 */
	public function test_activation_admin_notice() {

		delete_option( 'wp-rest-api-log-plugin-activated' );
		$this->assertSame( '', $this->capture( array( 'WP_REST_API_Log_Settings', 'activation_admin_notice' ) ) );

		update_option( 'wp-rest-api-log-plugin-activated', '1' );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings', 'activation_admin_notice' ) );

		$this->assertStringContainsString( '<strong>REST API Log activated!</strong>', $html );
		$this->assertStringContainsString( 'options-general.php?page=wp-rest-api-log-settings', $html );

		// The notice is only shown once.
		$this->assertFalse( get_option( 'wp-rest-api-log-plugin-activated' ) );
	}

	/**
	 * Tests that activation creates the default settings without replacing
	 * saved ones.
	 *
	 * @return void
	 */
	public function test_activator() {

		require_once WP_REST_API_LOG_PATH . 'includes/class-wp-rest-api-log-activator.php';

		delete_option( 'wp-rest-api-log-settings-general' );
		delete_option( 'wp-rest-api-log-settings-headers' );
		delete_option( 'wp-rest-api-log-plugin-activated' );
		update_option( 'wp-rest-api-log-settings-routes', array( 'ignore-core-oembed' => '0' ) );

		WP_REST_API_Log_Activator::activate();

		$this->assertSame( WP_REST_API_Log_Settings_General::get_default_settings(), get_option( 'wp-rest-api-log-settings-general' ) );
		$this->assertSame( WP_REST_API_Log_Settings_Headers::get_default_settings(), get_option( 'wp-rest-api-log-settings-headers' ) );
		$this->assertSame( array( 'ignore-core-oembed' => '0' ), get_option( 'wp-rest-api-log-settings-routes' ) );
		$this->assertSame( '1', get_option( 'wp-rest-api-log-plugin-activated' ) );

		// The deactivation hook is a placeholder.
		$this->assertNull( WP_REST_API_Log_Settings::deactivation_hook() );
	}

	/**
	 * Tests that the settings page is added to the Settings menu.
	 *
	 * @return void
	 */
	public function test_admin_menu() {

		global $submenu;

		wp_set_current_user( self::$admin_id );

		WP_REST_API_Log_Settings::admin_menu();

		$slugs = wp_list_pluck( $submenu['options-general.php'], 2 );
		$this->assertContains( 'wp-rest-api-log-settings', $slugs );
	}

	/**
	 * Tests rendering the settings page for a tab.
	 *
	 * @return void
	 */
	public function test_options_page() {

		wp_set_current_user( self::$admin_id );
		WP_REST_API_Log_Settings_Routes::register_routes_settings();

		$_GET = array( 'tab' => 'wp-rest-api-log-settings-routes' );

		$html = $this->capture( array( 'WP_REST_API_Log_Settings', 'options_page' ) );

		$this->assertStringContainsString( 'REST API Log</h2>', $html );
		$this->assertMatchesRegularExpression( '/class="nav-tab nav-tab-active" href="[^"]*tab=wp-rest-api-log-settings-routes"/', $html );
		$this->assertMatchesRegularExpression( '/class="nav-tab " href="[^"]*tab=wp-rest-api-log-settings-general"/', $html );
		$this->assertStringContainsString( "name='option_page' value='wp-rest-api-log-settings-routes'", $html );
		$this->assertStringContainsString( 'wp-rest-api-log-settings-routes[route-filters]', $html );
		$this->assertStringContainsString( 'type="submit"', $html );
		$this->assertSame( 0, did_action( 'wp-rest-api-log-settings-updated' ) );
	}

	/**
	 * Tests that the General tab is the default, that the Help tab has no
	 * save button, and that the settings-updated action fires after a save.
	 *
	 * @return void
	 */
	public function test_options_page_default_tab_and_help_tab() {

		wp_set_current_user( self::$admin_id );

		$_SERVER['HTTPS'] = 'on';
		$html             = $this->capture( array( 'WP_REST_API_Log_Settings', 'options_page' ) );
		unset( $_SERVER['HTTPS'] );

		$this->assertMatchesRegularExpression( '/class="nav-tab nav-tab-active" href="https:[^"]*tab=wp-rest-api-log-settings-general"/', $html );

		$_GET = array(
			'tab'              => 'wp-rest-api-log-settings-help',
			'settings-updated' => 'true',
		);

		$html = $this->capture( array( 'WP_REST_API_Log_Settings', 'options_page' ) );

		$this->assertStringNotContainsString( 'type="submit"', $html );
		$this->assertSame( 1, did_action( 'wp-rest-api-log-settings-updated' ) );
	}

	/**
	 * Tests the production database notice on the settings screen.
	 *
	 * @return void
	 */
	public function test_display_db_notice() {

		delete_option( 'wp-rest-api-log-db-notice-dismissed' );

		set_current_screen( 'dashboard' );
		$this->assertSame( '', $this->capture( array( 'WP_REST_API_Log_Settings_General', 'display_db_notice' ) ) );

		set_current_screen( 'settings_page_wp-rest-api-log-settings' );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings_General', 'display_db_notice' ) );
		$this->assertStringContainsString( 'id="wp-rest-api-log-admin-db-notice"', $html );
		$this->assertStringContainsString( 'wp-rest-api-log-db-notice-dismiss', $html );

		// Dismissing the notice hides it.
		WP_REST_API_Log_Settings_General::dismiss_db_notice();
		$this->assertSame( '1', get_option( 'wp-rest-api-log-db-notice-dismissed' ) );
		$this->assertSame( '', $this->capture( array( 'WP_REST_API_Log_Settings_General', 'display_db_notice' ) ) );
	}

	/**
	 * Tests the purge button shown on the settings page.
	 *
	 * @return void
	 */
	public function test_get_purge_button_html() {

		$db = new WP_REST_API_Log_DB();
		$db->insert( array( 'route' => '/one' ) );
		$db->insert( array( 'route' => '/two' ) );

		// Only built on the plugin's settings page.
		$this->assertSame( '', WP_REST_API_Log_Settings_General::get_purge_button_html() );

		$_GET = array( 'page' => 'wp-rest-api-log-settings' );

		$html = WP_REST_API_Log_Settings_General::get_purge_button_html();
		$this->assertStringContainsString( 'Purge All 2 Entries Now', $html );
		$this->assertStringContainsString( 'wp-rest-api-log-purge-all-spinner', $html );

		add_filter( 'get_purge_button_html', '__return_empty_string' );
		$this->assertSame( '', WP_REST_API_Log_Settings_General::get_purge_button_html() );

		// No button when the log is empty.
		remove_filter( 'get_purge_button_html', '__return_empty_string' );
		WP_REST_API_Log_DB::purge_all_log_entries();
		$this->assertSame( '', WP_REST_API_Log_Settings_General::get_purge_button_html() );
	}

	/**
	 * Tests that the plugin's languages folder is registered for its text
	 * domain.
	 *
	 * @return void
	 */
	public function test_i18n() {

		global $wp_textdomain_registry;

		$original = $wp_textdomain_registry;

		// Records the custom paths instead of storing them.
		$wp_textdomain_registry = new class() extends WP_Textdomain_Registry {

			/**
			 * Custom paths keyed by text domain.
			 *
			 * @var array
			 */
			public $recorded_paths = array();

			/**
			 * Records a custom path.
			 *
			 * @param string $domain Text domain.
			 * @param string $path   Language directory path.
			 * @return void
			 */
			public function set_custom_path( $domain, $path ) {
				$this->recorded_paths[ $domain ] = $path;
			}
		};

		WP_REST_API_Log_i18n::plugins_loaded();

		$paths                  = $wp_textdomain_registry->recorded_paths;
		$wp_textdomain_registry = $original;

		$this->assertArrayHasKey( 'wp-rest-api-log', $paths );
		$this->assertStringEndsWith( '/languages', $paths['wp-rest-api-log'] );
	}
}
