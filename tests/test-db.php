<?php
/**
 * Class WP_REST_API_Log_Test_DB
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for inserting and searching log entries.
 */
class WP_REST_API_Log_Test_DB extends WP_UnitTestCase {

	/**
	 * Database helper under test.
	 *
	 * @var WP_REST_API_Log_DB
	 */
	protected $db;

	/**
	 * Original $_SERVER values changed by the tests.
	 *
	 * @var array
	 */
	protected $server_backup = array();

	/**
	 * Sets up each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->db            = new WP_REST_API_Log_DB();
		$this->server_backup = $_SERVER;
	}

	/**
	 * Restores $_SERVER after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_SERVER = $this->server_backup;
		parent::tear_down();
	}

	/**
	 * Inserts a log entry with a backdated post date.
	 *
	 * @param  array  $args Insert arguments.
	 * @param  string $date Post date in MySQL format.
	 * @return int Post ID.
	 */
	protected function insert_with_date( $args, $date ) {
		$post_id = $this->db->insert( $args );

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
	 * Tests that the insert action and WHERE filters are registered.
	 *
	 * @return void
	 */
	public function test_plugins_loaded_adds_hooks() {
		$db = new WP_REST_API_Log_DB();
		$db->plugins_loaded();

		$this->assertSame( 10, has_action( 'wp-rest-api-log-insert', array( $db, 'insert' ) ) );
		$this->assertSame( 10, has_filter( 'posts_where', array( $db, 'add_where_route' ) ) );
		$this->assertSame( 10, has_filter( 'posts_where', array( $db, 'add_where_post_id' ) ) );
	}

	/**
	 * Tests that an insert stores the post, terms and post meta.
	 *
	 * @return void
	 */
	public function test_insert_stores_entry() {

		$user_id = self::factory()->user->create( array( 'user_login' => 'log_tester' ) );
		wp_set_current_user( $user_id );

		$_SERVER['REMOTE_ADDR']          = '10.0.0.1';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.1';
		$_SERVER['REQUEST_METHOD']       = 'POST';

		$post_id = $this->db->insert(
			array(
				'route'        => '/wp/v2/posts',
				'status'       => '201',
				'milliseconds' => 42,
				'request'      => array(
					'body'         => '{"title":"hello"}',
					'query_params' => array(
						'context' => 'edit',
						'empty'   => '',
					),
					'body_params'  => array(
						'title' => 'hello',
					),
				),
				'response'     => array(
					'body' => array( 'id' => 5 ),
				),
				'post_meta'    => array(
					'_custom_meta' => 'custom value',
				),
			)
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		global $wp_rest_api_log_new_entry_id;
		$this->assertSame( $post_id, $wp_rest_api_log_new_entry_id );

		$post = get_post( $post_id );
		$this->assertSame( WP_REST_API_Log_DB::POST_TYPE, $post->post_type );
		$this->assertSame( '/wp/v2/posts', $post->post_title );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertSame( array( 'id' => 5 ), json_decode( $post->post_content, true ) );
		$this->assertStringStartsWith( 'wp-v2-posts-', $post->post_name );

		// The method defaults to $_SERVER['REQUEST_METHOD'].
		$this->assertSame( array( 'POST' ), wp_get_post_terms( $post_id, WP_REST_API_Log_DB::TAXONOMY_METHOD, array( 'fields' => 'names' ) ) );
		$this->assertSame( array( '201' ), wp_get_post_terms( $post_id, WP_REST_API_Log_DB::TAXONOMY_STATUS, array( 'fields' => 'names' ) ) );
		$this->assertSame( array( 'WP REST API' ), wp_get_post_terms( $post_id, WP_REST_API_Log_DB::TAXONOMY_SOURCE, array( 'fields' => 'names' ) ) );

		$this->assertSame( '10.0.0.1', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_IP_ADDRESS, true ) );
		$this->assertSame( '192.168.1.1', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_HTTP_X_FORWARDED_FOR, true ) );
		$this->assertSame( 'log_tester', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_REQUEST_USER, true ) );
		$this->assertSame( '42', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_MILLISECONDS, true ) );
		$this->assertSame( '{"title":"hello"}', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_REQUEST_BODY, true ) );
		$this->assertSame( 'custom value', get_post_meta( $post_id, '_custom_meta', true ) );

		$this->assertSame( 'edit', get_post_meta( $post_id, 'request_query_params|context', true ) );
		$this->assertSame( 'hello', get_post_meta( $post_id, 'request_body_params|title', true ) );

		// Empty values are not stored.
		$this->assertSame( array(), get_post_meta( $post_id, 'request_query_params|empty', false ) );
	}

	/**
	 * Tests that single-value header arrays are flattened, while multi-value
	 * headers are kept as arrays.
	 *
	 * @return void
	 */
	public function test_insert_flattens_single_value_headers() {

		// Keep redaction out of the way for this test.
		update_option(
			'wp-rest-api-log-settings-headers',
			array(
				'redacted-request-headers'  => '',
				'redacted-response-headers' => '',
			)
		);

		$post_id = $this->db->insert(
			array(
				'route'    => '/wp/v2/users',
				'request'  => array(
					'body'    => '',
					'headers' => array(
						'content_type' => array( 'application/json' ),
						'accept'       => array( 'text/html', 'application/json' ),
					),
				),
				'response' => array(
					'body'    => '',
					'headers' => array(
						'X-Single' => array( 'one' ),
						'X-Empty'  => '',
					),
				),
			)
		);

		$this->assertSame( 'application/json', get_post_meta( $post_id, 'request_headers|content_type', true ) );
		$this->assertSame( array( 'text/html', 'application/json' ), get_post_meta( $post_id, 'request_headers|accept', true ) );
		$this->assertSame( 'one', get_post_meta( $post_id, 'response_headers|X-Single', true ) );
		$this->assertSame( array(), get_post_meta( $post_id, 'response_headers|X-Empty', false ) );
	}

	/**
	 * Tests that invalid methods are stored as GET and the status is cast to
	 * an integer.
	 *
	 * @return void
	 */
	public function test_insert_sanitizes_method_and_status() {

		$post_id = $this->db->insert(
			array(
				'route'  => '/wp/v2/posts',
				'method' => 'TRACE',
				'status' => '404abc',
				'source' => 'Unit Test',
			)
		);

		$entry = new WP_REST_API_Log_Entry( $post_id );

		$this->assertSame( 'GET', $entry->method );
		$this->assertSame( '404', $entry->status );
		$this->assertSame( 'Unit Test', $entry->source );
	}

	/**
	 * Tests that a literal "\n" in the request and response bodies is
	 * converted to a real line break.
	 *
	 * @return void
	 */
	public function test_insert_converts_escaped_newlines() {

		$post_id = $this->db->insert(
			array(
				'route'    => '/wp/v2/posts',
				'request'  => array(
					'body' => 'line one\nline two',
				),
				'response' => array(
					'body' => "line one\nline two",
				),
			)
		);

		$this->assertSame( 'line one' . PHP_EOL . 'line two', get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_REQUEST_BODY, true ) );

		// wp_json_encode() turns the newline into "\n", which is then
		// replaced with a real line break.
		$this->assertSame( '"line one' . PHP_EOL . 'line two"', get_post( $post_id )->post_content );
	}

	/**
	 * Tests that the elapsed time is calculated from the plugin's start time
	 * when none is supplied.
	 *
	 * @return void
	 */
	public function test_insert_calculates_milliseconds() {

		global $wp_rest_api_log_start;
		$original_start = $wp_rest_api_log_start;

		$wp_rest_api_log_start = WP_REST_API_Log_Common::current_milliseconds() - 1500;

		$post_id = $this->db->insert( array( 'route' => '/wp/v2/posts' ) );

		$wp_rest_api_log_start = $original_start;

		$milliseconds = absint( get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_MILLISECONDS, true ) );
		$this->assertGreaterThanOrEqual( 1500, $milliseconds );
		$this->assertLessThan( 60000, $milliseconds );
	}

	/**
	 * Tests the pre-insert filters.
	 *
	 * @return void
	 */
	public function test_insert_filters() {

		$args_filter = function ( $args ) {
			$args['route'] = '/filtered/route';
			return $args;
		};

		$post_filter = function ( $new_post, $args ) {
			$new_post['post_title'] = $args['route'] . '/post';
			return $new_post;
		};

		add_filter( 'wp-rest-api-log-entries-pre-insert', $args_filter );
		add_filter( 'wp-rest-api-log-entries-pre-insert-new-post', $post_filter, 10, 2 );

		$post_id = $this->db->insert( array( 'route' => '/wp/v2/posts' ) );

		$this->assertSame( '/filtered/route/post', get_post( $post_id )->post_title );
	}

	/**
	 * Tests that nothing else is stored when the post cannot be created.
	 *
	 * @return void
	 */
	public function test_insert_returns_zero_when_post_is_not_created() {

		global $wp_rest_api_log_new_entry_id;
		$wp_rest_api_log_new_entry_id = null;

		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		$post_id = $this->db->insert( array( 'route' => '/wp/v2/posts' ) );

		$this->assertSame( 0, $post_id );
		$this->assertNull( $wp_rest_api_log_new_entry_id );
	}

	/**
	 * Tests that the insert action writes an entry.
	 *
	 * @return void
	 */
	public function test_insert_action() {

		do_action( 'wp-rest-api-log-insert', array( 'route' => '/from/action' ) );

		global $wp_rest_api_log_new_entry_id;
		$this->assertSame( '/from/action', get_post( $wp_rest_api_log_new_entry_id )->post_title );
	}

	/**
	 * Tests searching by route with each match type.
	 *
	 * @return void
	 */
	public function test_search_by_route() {

		$posts  = $this->db->insert( array( 'route' => '/wp/v2/posts' ) );
		$post_1 = $this->db->insert( array( 'route' => '/wp/v2/posts/1' ) );
		$users  = $this->db->insert( array( 'route' => '/wp/v2/users' ) );

		$search = function ( $route, $match_type ) {
			return wp_list_pluck(
				$this->db->search(
					array(
						'to'               => '',
						'route'            => $route,
						'route_match_type' => $match_type,
					)
				),
				'ID'
			);
		};

		$this->assertEqualSets( array( $posts ), $search( '/wp/v2/posts', 'exact' ) );
		$this->assertEqualSets( array( $posts, $post_1 ), $search( '/wp/v2/posts', 'starts_with' ) );
		$this->assertEqualSets( array( $users ), $search( '/users', 'ends_with' ) );
		$this->assertEqualSets( array( $posts, $post_1, $users ), $search( 'v2', 'wildcard' ) );
	}

	/**
	 * Tests searching by method and status, including comma separated lists.
	 *
	 * @return void
	 */
	public function test_search_by_method_and_status() {

		$get_200  = $this->db->insert(
			array(
				'route'  => '/a',
				'method' => 'GET',
				'status' => 200,
			)
		);
		$post_201 = $this->db->insert(
			array(
				'route'  => '/b',
				'method' => 'POST',
				'status' => 201,
			)
		);
		$post_400 = $this->db->insert(
			array(
				'route'  => '/c',
				'method' => 'POST',
				'status' => 400,
			)
		);

		$ids = function ( $args ) {
			$args['to']     = '';
			$args['fields'] = 'ids';
			return $this->db->search( $args );
		};

		$this->assertEqualSets( array( $post_201, $post_400 ), $ids( array( 'method' => 'POST' ) ) );
		$this->assertEqualSets( array( $get_200, $post_201, $post_400 ), $ids( array( 'method' => 'GET,POST' ) ) );
		$this->assertEqualSets( array( $post_400 ), $ids( array( 'status' => '400' ) ) );
		$this->assertEqualSets( array( $get_200, $post_201 ), $ids( array( 'status' => '200,201' ) ) );
		$this->assertEqualSets(
			array( $post_201 ),
			$ids(
				array(
					'method' => 'POST',
					'status' => '201',
				)
			)
		);
	}

	/**
	 * Tests searching by ID and by an ID range.
	 *
	 * @return void
	 */
	public function test_search_by_id() {

		$first  = $this->db->insert( array( 'route' => '/first' ) );
		$second = $this->db->insert( array( 'route' => '/second' ) );
		$third  = $this->db->insert( array( 'route' => '/third' ) );

		$ids = function ( $args ) {
			$args['to']     = '';
			$args['fields'] = 'ids';
			return $this->db->search( $args );
		};

		$this->assertSame( array( $second ), $ids( array( 'id' => $second ) ) );
		$this->assertEqualSets( array( $second, $third ), $ids( array( 'after_id' => $first ) ) );
		$this->assertEqualSets( array( $first, $second ), $ids( array( 'before_id' => $third ) ) );
		$this->assertSame(
			array( $second ),
			$ids(
				array(
					'after_id'  => $first,
					'before_id' => $third,
				)
			)
		);
	}

	/**
	 * Tests searching by date range and paging.
	 *
	 * @return void
	 */
	public function test_search_by_date_and_page() {

		$old    = $this->insert_with_date( array( 'route' => '/old' ), '2020-01-01 00:00:00' );
		$middle = $this->insert_with_date( array( 'route' => '/middle' ), '2021-01-01 00:00:00' );
		$new    = $this->insert_with_date( array( 'route' => '/new' ), '2022-01-01 00:00:00' );

		$this->assertEqualSets(
			array( $middle, $new ),
			$this->db->search(
				array(
					'from'   => '2020-06-01 00:00:00',
					'fields' => 'ids',
				)
			)
		);

		$this->assertEqualSets(
			array( $old, $middle ),
			$this->db->search(
				array(
					'to'     => '2021-06-01 00:00:00',
					'fields' => 'ids',
				)
			)
		);

		// Newest first, one per page.
		$page_two = $this->db->search(
			array(
				'posts_per_page' => 1,
				'page'           => 2,
				'fields'         => 'ids',
			)
		);
		$this->assertSame( array( $middle ), $page_two );

		// A page past the end returns an empty array.
		$this->assertSame(
			array(),
			$this->db->search(
				array(
					'posts_per_page' => 1,
					'page'           => 10,
				)
			)
		);
	}

	/**
	 * Documents that a search with the default "to" date skips entries
	 * logged in the current second.
	 *
	 * Bug: WP_REST_API_Log_DB::search() defaults "to" to
	 * current_time( 'mysql' ) and passes it as the "before" date query, which
	 * is exclusive. Entries created in the same second as the search are left
	 * out. The REST /entries route has the same default.
	 *
	 * @return void
	 */
	public function test_search_default_to_excludes_entries_from_the_current_second() {

		$post_id = $this->db->insert( array( 'route' => '/just-now' ) );

		// Pin the post date to the "to" value the search will use.
		$now = current_time( 'mysql' );
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_date'     => $now,
				'post_date_gmt' => get_gmt_from_date( $now ),
			)
		);

		$this->assertSame(
			array(),
			$this->db->search(
				array(
					'to'     => $now,
					'fields' => 'ids',
				)
			),
			'Current behavior: an entry dated exactly at "to" is excluded.'
		);

		$this->assertSame(
			array( $post_id ),
			$this->db->search(
				array(
					'to'     => '',
					'fields' => 'ids',
				)
			)
		);
	}

	/**
	 * Tests that the WHERE filters leave unrelated queries alone.
	 *
	 * @return void
	 */
	public function test_where_filters_ignore_other_queries() {

		$query = new WP_Query();

		$this->assertSame( ' AND 1=1', $this->db->add_where_route( ' AND 1=1', $query ) );
		$this->assertSame( ' AND 1=1', $this->db->add_where_post_id( ' AND 1=1', $query ) );
	}

	/**
	 * Tests the WHERE clauses added for route and ID searches.
	 *
	 * @return void
	 */
	public function test_where_filters_add_clauses() {

		global $wpdb;

		$query = new WP_Query();
		$query->set( '_wp-rest-api-log-route', '/wp/v2' );
		$query->set( '_wp-rest-api-log-route-match-type', 'starts_with' );
		$query->set( '_wp-rest-api-log-after-id', 5 );
		$query->set( '_wp-rest-api-log-before-id', 10 );

		$this->assertSame( " AND {$wpdb->posts}.post_title like '/wp/v2%'", $wpdb->remove_placeholder_escape( $this->db->add_where_route( '', $query ) ) );
		$this->assertSame( " AND {$wpdb->posts}.ID > 5 AND {$wpdb->posts}.ID < 10", $this->db->add_where_post_id( '', $query ) );
	}

	/**
	 * Tests getting and purging every log entry.
	 *
	 * @return void
	 */
	public function test_get_all_log_ids_and_purge() {

		$first  = $this->db->insert( array( 'route' => '/first' ) );
		$second = $this->db->insert( array( 'route' => '/second' ) );
		$other  = self::factory()->post->create();

		$this->assertEqualSets( array( $first, $second ), WP_REST_API_Log_DB::get_all_log_ids() );

		WP_REST_API_Log_DB::purge_all_log_entries();

		$this->assertSame( array(), WP_REST_API_Log_DB::get_all_log_ids() );
		$this->assertNull( get_post( $first ) );
		$this->assertInstanceOf( 'WP_Post', get_post( $other ) );
	}
}
