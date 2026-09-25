<?php
/**
 * Class WP_REST_API_Log_Test_Controller
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the plugin's own REST API routes.
 */
class WP_REST_API_Log_Test_Controller extends WP_UnitTestCase {

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
	 * Creates the users shared by the tests.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id      = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$subscriber_id = $factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Sets up a fresh REST server for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	/**
	 * Removes the REST server after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		unset( $_SERVER['HTTPS'] );

		parent::tear_down();
	}

	/**
	 * Inserts a log entry dated in the past, so the default "to" date of the
	 * entries route does not exclude it.
	 *
	 * @param  array  $args Insert arguments.
	 * @param  string $date Post date.
	 * @return int Post ID.
	 */
	protected function insert_entry( $args = array(), $date = '2020-01-01 00:00:00' ) {

		$db      = new WP_REST_API_Log_DB();
		$post_id = $db->insert( wp_parse_args( $args, array( 'route' => '/wp/v2/posts' ) ) );

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
	 * Dispatches a REST request.
	 *
	 * @param  string $method HTTP method.
	 * @param  string $route  Route.
	 * @param  array  $params Request parameters.
	 * @return WP_REST_Response
	 */
	protected function dispatch( $method, $route, $params = array() ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Tests that the plugin's routes are registered.
	 *
	 * @return void
	 */
	public function test_routes_are_registered() {

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wp-rest-api-log/entries', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/entry/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/entry', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/batch-purge-all', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/routes', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/entry/(?P<id>[\d]+)/(?P<rr>request)/(?P<property>body_params)/download', $routes );
		$this->assertArrayHasKey( '/wp-rest-api-log/entry/(?P<id>[\d]+)/(?P<rr>response)/(?P<property>headers)/download', $routes );

		$this->assertSame( 10, has_action( 'rest_api_init', array( 'WP_REST_API_Log_Controller', 'register_rest_routes' ) ) );
		$this->assertSame( 10, has_action( 'rest_api_init', array( 'WP_REST_API_Log_Controller', 'register_download_routes' ) ) );
	}

	/**
	 * Tests that anonymous users and subscribers cannot read entries.
	 *
	 * @return void
	 */
	public function test_get_items_requires_permission() {

		$this->insert_entry();

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries' );
		$this->assertSame( 401, $response->get_status() );

		wp_set_current_user( self::$subscriber_id );
		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries' );
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests reading entries as an administrator.
	 *
	 * @return void
	 */
	public function test_get_items() {

		wp_set_current_user( self::$admin_id );

		$get  = $this->insert_entry(
			array(
				'route'  => '/wp/v2/posts',
				'method' => 'GET',
			),
			'2020-01-01 00:00:00'
		);
		$post = $this->insert_entry(
			array(
				'route'  => '/wp/v2/users',
				'method' => 'POST',
				'status' => 201,
			),
			'2020-01-02 00:00:00'
		);

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $post, $get ), wp_list_pluck( $response->get_data(), 'ID' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries', array( 'method' => 'POST' ) );
		$this->assertSame( array( $post ), wp_list_pluck( $response->get_data(), 'ID' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries', array( 'status' => '201' ) );
		$this->assertSame( array( $post ), wp_list_pluck( $response->get_data(), 'ID' ) );

		$response = $this->dispatch(
			'GET',
			'/wp-rest-api-log/entries',
			array(
				'route'            => '/wp/v2/p',
				'route-match-type' => 'starts_with',
			)
		);
		$this->assertSame( array( $get ), wp_list_pluck( $response->get_data(), 'ID' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries', array( 'after-id' => $get ) );
		$this->assertSame( array( $post ), wp_list_pluck( $response->get_data(), 'ID' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries', array( 'from' => '2020-01-01 12:00:00' ) );
		$this->assertSame( array( $post ), wp_list_pluck( $response->get_data(), 'ID' ) );
	}

	/**
	 * Documents that the records-per-page parameter is ignored.
	 *
	 * Bug: WP_REST_API_Log_Controller::get_items() passes the value as
	 * "records_per_page", but WP_REST_API_Log_DB::search() reads
	 * "posts_per_page", so every request returns up to 50 entries.
	 *
	 * @return void
	 */
	public function test_get_items_ignores_records_per_page() {

		wp_set_current_user( self::$admin_id );

		$this->insert_entry();
		$this->insert_entry();
		$this->insert_entry();

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entries', array( 'records-per-page' => 1 ) );

		// Current behavior: all three entries are returned instead of one.
		$this->assertCount( 3, $response->get_data() );
	}

	/**
	 * Tests reading a single entry.
	 *
	 * @return void
	 */
	public function test_get_item() {

		wp_set_current_user( self::$admin_id );

		$post_id = $this->insert_entry( array( 'route' => '/single' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/entry/' . $post_id );
		$this->assertSame( 200, $response->get_status() );

		$entry = $response->get_data();
		$this->assertInstanceOf( 'WP_REST_API_Log_Entry', $entry );
		$this->assertSame( '/single', $entry->route );

		// A regular post is not a log entry.
		$other_post = self::factory()->post->create();
		$response   = $this->dispatch( 'GET', '/wp-rest-api-log/entry/' . $other_post );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->as_error()->get_error_code() );
	}

	/**
	 * Tests validating entry IDs.
	 *
	 * @return void
	 */
	public function test_validate_entry_id() {

		$post_id = $this->insert_entry();

		$this->assertTrue( WP_REST_API_Log_Controller::validate_entry_id( $post_id ) );

		$error = WP_REST_API_Log_Controller::validate_entry_id( PHP_INT_MAX );
		$this->assertWPError( $error );
		$this->assertSame( 'invalid_entry_id', $error->get_error_code() );
		$this->assertSame( array( 'status' => 404 ), $error->get_error_data() );

		$this->assertFalse( WP_REST_API_Log_Controller::get_entry( PHP_INT_MAX ) );
	}

	/**
	 * Documents that an entry ID below 1 causes a fatal error.
	 *
	 * Bug: WP_REST_API_Log_Controller::validate_entry_id() calls
	 * invalid_entry_id_error() as a global function instead of
	 * self::invalid_entry_id_error(), so a request such as
	 * GET /wp-rest-api-log/entry/0 ends with "Call to undefined function".
	 *
	 * @return void
	 */
	public function test_validate_entry_id_below_one_is_fatal() {
		$this->expectException( 'Error' );
		$this->expectExceptionMessage( 'invalid_entry_id_error' );

		WP_REST_API_Log_Controller::validate_entry_id( 0 );
	}

	/**
	 * Tests the read permission check and its filter.
	 *
	 * @return void
	 */
	public function test_get_permissions_check() {

		$this->assertFalse( WP_REST_API_Log_Controller::get_permissions_check() );

		wp_set_current_user( self::$admin_id );
		$this->assertTrue( WP_REST_API_Log_Controller::get_permissions_check() );

		add_filter( 'wp-rest-api-log-can-view-entries', '__return_false' );
		$this->assertFalse( WP_REST_API_Log_Controller::get_permissions_check() );
	}

	/**
	 * Tests that nobody can read entries if the post type is missing.
	 *
	 * @return void
	 */
	public function test_get_permissions_check_without_post_type() {

		wp_set_current_user( self::$admin_id );

		unregister_post_type( WP_REST_API_Log_DB::POST_TYPE );
		$allowed = WP_REST_API_Log_Controller::get_permissions_check();

		WP_REST_API_Log_Post_Type::register_custom_post_types();
		WP_REST_API_Log_Taxonomies::register_custom_taxonomies();

		$this->assertFalse( $allowed );
	}

	/**
	 * Tests the delete permission check.
	 *
	 * @return void
	 */
	public function test_delete_items_permissions_check() {

		wp_set_current_user( self::$subscriber_id );
		$this->assertFalse( WP_REST_API_Log_Controller::delete_items_permissions_check() );

		wp_set_current_user( self::$admin_id );
		$this->assertTrue( WP_REST_API_Log_Controller::delete_items_permissions_check() );

		add_filter( 'wp-rest-api-log-can-delete-entries', '__return_false' );
		$this->assertFalse( WP_REST_API_Log_Controller::delete_items_permissions_check() );
	}

	/**
	 * Tests listing the distinct logged routes.
	 *
	 * @return void
	 */
	public function test_get_routes() {

		wp_set_current_user( self::$admin_id );

		$this->insert_entry( array( 'route' => '/wp/v2/posts' ) );
		$this->insert_entry( array( 'route' => '/wp/v2/posts' ) );
		$this->insert_entry( array( 'route' => '/wp/v2/users' ) );

		$response = $this->dispatch( 'GET', '/wp-rest-api-log/routes' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEqualSets( array( '/wp/v2/posts', '/wp/v2/users' ), $response->get_data() );
	}

	/**
	 * Tests purging every entry.
	 *
	 * @return void
	 */
	public function test_purge_log() {

		$this->insert_entry();
		$this->insert_entry();

		// Subscribers cannot purge the log.
		wp_set_current_user( self::$subscriber_id );
		$response = $this->dispatch( 'DELETE', '/wp-rest-api-log/entries' );
		$this->assertSame( 403, $response->get_status() );
		$this->assertCount( 2, WP_REST_API_Log_DB::get_all_log_ids() );

		wp_set_current_user( self::$admin_id );
		$response = $this->dispatch( 'DELETE', '/wp-rest-api-log/entries' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'success' => true ), $response->get_data() );
		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
	}

	/**
	 * Tests purging entries in batches, oldest first.
	 *
	 * @return void
	 */
	public function test_batch_purge_log() {

		wp_set_current_user( self::$admin_id );

		$oldest = $this->insert_entry( array(), '2020-01-01 00:00:00' );
		$middle = $this->insert_entry( array(), '2020-01-02 00:00:00' );
		$newest = $this->insert_entry( array(), '2020-01-03 00:00:00' );

		add_filter(
			'wp-rest-api-log-batch-purge-query-args',
			function ( $query_args ) {
				$query_args['posts_per_page'] = 2;
				return $query_args;
			}
		);

		$response = $this->dispatch( 'DELETE', '/wp-rest-api-log/batch-purge-all' );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['entries_left'] );
		$this->assertSame( '1 entries remaining...', $data['entries_left_formatted'] );
		$this->assertSame( array( $newest ), WP_REST_API_Log_DB::get_all_log_ids() );
		$this->assertNull( get_post( $oldest ) );
		$this->assertNull( get_post( $middle ) );

		// The purge request itself is not logged.
		$this->assertTrue( apply_filters( 'wp-rest-api-log-bypass-insert', false, null, new WP_REST_Request( 'GET', '/wp/v2/posts' ), null ) );
	}

	/**
	 * Documents that deleting entries by age causes a fatal error.
	 *
	 * Bug: WP_REST_API_Log_Controller::delete_items() calls
	 * WP_REST_API_Log_DB::delete(), which does not exist, so
	 * DELETE /wp-rest-api-log/entry ends with "Call to undefined method".
	 *
	 * @return void
	 */
	public function test_delete_items_is_fatal() {
		$this->expectException( 'Error' );
		$this->expectExceptionMessage( 'WP_REST_API_Log_DB::delete()' );

		$request = new WP_REST_Request( 'DELETE', '/wp-rest-api-log/entry' );
		$request->set_param( 'older-than-seconds', 60 );

		WP_REST_API_Log_Controller::delete_items( $request );
	}

	/**
	 * Tests the list of downloadable properties and its filter.
	 *
	 * @return void
	 */
	public function test_get_download_routes() {

		$routes = WP_REST_API_Log_Controller::get_download_routes();

		$this->assertSame( array( 'body_params', 'query_params', 'body', 'headers' ), $routes['request'] );
		$this->assertSame( array( 'body', 'headers' ), $routes['response'] );

		add_filter(
			'wp-rest-api-log-download-routes',
			function ( $routes ) {
				unset( $routes['request'] );
				return $routes;
			}
		);

		$this->assertSame( array( 'response' ), array_keys( WP_REST_API_Log_Controller::get_download_routes() ) );
	}

	/**
	 * Tests the download URLs built for an entry.
	 *
	 * @return void
	 */
	public function test_get_download_urls() {

		wp_set_current_user( self::$admin_id );

		$entry = new WP_REST_API_Log_Entry( $this->insert_entry() );
		$urls  = WP_REST_API_Log_Controller::get_download_urls( $entry );

		$this->assertSame( array( 'request', 'response' ), array_keys( $urls ) );
		$this->assertSame( array( 'body_params', 'query_params', 'body', 'headers' ), array_keys( $urls['request'] ) );

		$url = $urls['response']['headers'];
		wp_parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );

		// The route is in the path with pretty permalinks, otherwise in the
		// rest_route query argument.
		$route = isset( $query['rest_route'] ) ? $query['rest_route'] : wp_parse_url( $url, PHP_URL_PATH );
		$this->assertStringEndsWith( "/wp-rest-api-log/entry/{$entry->ID}/response/headers/download", $route );
		$this->assertSame( wp_hash( wp_nonce_tick() . 'wp-rest-api-log-download-response-headers' ), $query['hash'] );
		$this->assertSame( 1, wp_verify_nonce( $query['_wpnonce'], 'wp_rest' ) );

		// URLs are forced to https on SSL requests.
		$_SERVER['HTTPS'] = 'on';
		$urls             = WP_REST_API_Log_Controller::get_download_urls( $entry );
		$this->assertStringStartsWith( 'https://', $urls['request']['body'] );

		add_filter(
			'wp-rest-api-log-download-urls',
			function ( $urls, $filtered_entry ) use ( $entry ) {
				$this->assertSame( $entry, $filtered_entry );
				return array();
			},
			10,
			2
		);

		$this->assertSame( array(), WP_REST_API_Log_Controller::get_download_urls( $entry ) );
	}

	/**
	 * Tests the download permission check.
	 *
	 * @return void
	 */
	public function test_download_permissions_check() {

		$post_id = $this->insert_entry();

		$request = new WP_REST_Request( 'GET', "/wp-rest-api-log/entry/{$post_id}/request/body/download" );
		$request->set_param( 'rr', 'request' );
		$request->set_param( 'property', 'body' );
		$request->set_param( 'hash', wp_hash( wp_nonce_tick() . 'wp-rest-api-log-download-request-body' ) );

		// Logged out users cannot download, even with a valid hash.
		$this->assertFalse( WP_REST_API_Log_Controller::download_permissions_check( $request ) );

		wp_set_current_user( self::$admin_id );
		$this->assertTrue( WP_REST_API_Log_Controller::download_permissions_check( $request ) );

		// The hash is tied to the property.
		$request->set_param( 'property', 'headers' );
		$this->assertFalse( WP_REST_API_Log_Controller::download_permissions_check( $request ) );

		// A missing hash is rejected.
		$request->set_param( 'hash', '' );
		$this->assertFalse( WP_REST_API_Log_Controller::download_permissions_check( $request ) );

		add_filter(
			'wp-rest-api-log-can-download-entry',
			function ( $allowed, $rr, $property ) {
				$this->assertFalse( $allowed );
				$this->assertSame( 'request', $rr );
				$this->assertSame( 'headers', $property );
				return true;
			},
			10,
			3
		);

		$this->assertTrue( WP_REST_API_Log_Controller::download_permissions_check( $request ) );
	}

	/**
	 * Tests that the download route returns the entry and hooks up the file
	 * download handler.
	 *
	 * @return void
	 */
	public function test_download_json() {

		wp_set_current_user( self::$admin_id );

		$post_id = $this->insert_entry();

		$response = $this->dispatch(
			'GET',
			"/wp-rest-api-log/entry/{$post_id}/request/body/download",
			array( 'hash' => wp_hash( wp_nonce_tick() . 'wp-rest-api-log-download-request-body' ) )
		);

		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['wp-rest-api-log-download'] );
		$this->assertSame( $post_id, $data['entry']->ID );
		$this->assertSame( 10, has_filter( 'rest_pre_serve_request', array( 'WP_REST_API_Log_Controller', 'download_json_pre_serve_request' ) ) );

		// A bad hash is rejected.
		$response = $this->dispatch(
			'GET',
			"/wp-rest-api-log/entry/{$post_id}/request/body/download",
			array( 'hash' => 'bad' )
		);
		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Tests that regular responses are left for the REST API to serve.
	 *
	 * @return void
	 */
	public function test_download_json_pre_serve_request_ignores_other_responses() {

		$served = WP_REST_API_Log_Controller::download_json_pre_serve_request(
			false,
			new WP_REST_Response( array( 'id' => 1 ) ),
			new WP_REST_Request( 'GET', '/wp/v2/posts/1' ),
			rest_get_server()
		);

		$this->assertFalse( $served );
	}

	/**
	 * Runs the download handler for one entry property and returns the
	 * output and the filename.
	 *
	 * The handler calls header(), which PHPUnit reports as a warning because
	 * output has already started, so that warning is ignored here.
	 *
	 * @param  WP_REST_API_Log_Entry $entry    Log entry.
	 * @param  string                $rr       Either "request" or "response".
	 * @param  string                $property Entry property.
	 * @return array Output, filename and the served flag.
	 */
	protected function run_download( $entry, $rr, $property ) {

		$filename = '';
		$callback = function ( $name ) use ( &$filename ) {
			$filename = $name;
			return $name;
		};
		add_filter( 'wp-rest-api-log-download-filename', $callback );

		$request = new WP_REST_Request( 'GET', '/download' );
		$request->set_param( 'rr', $rr );
		$request->set_param( 'property', $property );

		$response = new WP_REST_Response(
			array(
				'wp-rest-api-log-download' => true,
				'entry'                    => $entry,
			)
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Ignores the "headers already sent" warning in the CLI.
		set_error_handler(
			function ( $errno, $errstr ) {
				return false !== strpos( $errstr, 'Cannot modify header information' );
			},
			E_WARNING
		);

		ob_start();
		$served = WP_REST_API_Log_Controller::download_json_pre_serve_request( false, $response, $request, rest_get_server() );
		$output = ob_get_clean();

		restore_error_handler();
		remove_filter( 'wp-rest-api-log-download-filename', $callback );

		return array(
			'output'   => $output,
			'filename' => $filename,
			'served'   => $served,
		);
	}

	/**
	 * Tests downloading array, JSON and plain text properties.
	 *
	 * @return void
	 */
	public function test_download_json_pre_serve_request() {

		update_option(
			'wp-rest-api-log-settings-headers',
			array(
				'redacted-request-headers'  => '',
				'redacted-response-headers' => '',
			)
		);

		$post_id = $this->insert_entry(
			array(
				'route'    => '/wp/v2/posts',
				'request'  => array(
					'body'    => 'plain text body',
					'headers' => array(
						'accept' => array( 'application/json' ),
					),
				),
				'response' => array(
					'body' => array( 'id' => 7 ),
				),
			)
		);

		$entry = new WP_REST_API_Log_Entry( $post_id );

		// Arrays are sent as pretty printed JSON.
		$result = $this->run_download( $entry, 'request', 'headers' );
		$this->assertTrue( $result['served'] );
		$this->assertSame( json_encode( array( 'accept' => 'application/json' ), JSON_PRETTY_PRINT ), $result['output'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Matches the handler.
		$this->assertSame( "wpv2posts-request-headers-{$post_id}.json", $result['filename'] );

		// Valid JSON strings are sent as they are.
		$result = $this->run_download( $entry, 'response', 'body' );
		$this->assertSame( $entry->response->body, $result['output'] );
		$this->assertSame( "wpv2posts-response-body-{$post_id}.json", $result['filename'] );

		// Anything else is sent as plain text.
		$result = $this->run_download( $entry, 'request', 'body' );
		$this->assertSame( 'plain text body', $result['output'] );
		$this->assertSame( "wpv2posts-request-body-{$post_id}.txt", $result['filename'] );

		// A string wrapped in braces that is not valid JSON keeps the json
		// extension.
		$entry->request->body = '{not: valid}';
		$result               = $this->run_download( $entry, 'request', 'body' );
		$this->assertSame( "wpv2posts-request-body-{$post_id}.json", $result['filename'] );
	}
}
