<?php
/**
 * Class WP_REST_API_Log_Test_Entry
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the log entry model and its request and response halves.
 */
class WP_REST_API_Log_Test_Entry extends WP_UnitTestCase {

	/**
	 * Inserts a log entry with request and response data.
	 *
	 * @return int Post ID.
	 */
	protected function insert_entry() {

		update_option(
			'wp-rest-api-log-settings-headers',
			array(
				'redacted-request-headers'  => '',
				'redacted-response-headers' => '',
			)
		);

		$db = new WP_REST_API_Log_DB();

		return $db->insert(
			array(
				'route'                => '/wp/v2/posts/1',
				'method'               => 'PUT',
				'status'               => 200,
				'source'               => 'WP REST API',
				'ip_address'           => '10.0.0.5',
				'user'                 => 'editor',
				'http_x_forwarded_for' => '172.16.0.1',
				'milliseconds'         => 123,
				'request'              => array(
					'body'         => '{"title":"<b>Hi</b>"}',
					'headers'      => array(
						'content_type' => array( 'application/json' ),
						'accept'       => array( 'text/html', '<script>' ),
					),
					'query_params' => array(
						'context' => '<em>edit</em>',
					),
					'body_params'  => array(
						'title' => '<b>Hi</b>',
					),
				),
				'response'             => array(
					'body'    => array( 'id' => 1 ),
					'headers' => array(
						'X-Test' => '<i>yes</i>',
					),
				),
			)
		);
	}

	/**
	 * Tests that an entry is loaded from a post ID.
	 *
	 * @return void
	 */
	public function test_entry_from_id() {

		$post_id = $this->insert_entry();
		$entry   = new WP_REST_API_Log_Entry( $post_id );
		$post    = get_post( $post_id );

		$this->assertSame( $post_id, $entry->ID );
		$this->assertSame( '/wp/v2/posts/1', $entry->route );
		$this->assertSame( $post->post_date, $entry->time );
		$this->assertSame( $post->post_date_gmt, $entry->time_gmt );
		$this->assertSame( 'PUT', $entry->method );
		$this->assertSame( '200', $entry->status );
		$this->assertSame( 'WP REST API', $entry->source );
		$this->assertSame( '10.0.0.5', $entry->ip_address );
		$this->assertSame( 'editor', $entry->user );
		$this->assertSame( '172.16.0.1', $entry->http_x_forwarded_for );
		$this->assertSame( 123, $entry->milliseconds );
		$this->assertSame( rest_url( 'wp-rest-api-log/entry/' . $post_id ), $entry->_links['self']['href'] );

		$this->assertInstanceOf( 'WP_REST_API_Log_API_Request', $entry->request );
		$this->assertSame( '{"title":"<b>Hi</b>"}', $entry->request->body );
		$this->assertSame(
			array(
				'content_type' => 'application/json',
				'accept'       => array( 'text/html', '<script>' ),
			),
			$entry->request->headers
		);
		$this->assertSame( array( 'context' => '<em>edit</em>' ), $entry->request->query_params );
		$this->assertSame( array( 'title' => '<b>Hi</b>' ), $entry->request->body_params );

		$this->assertInstanceOf( 'WP_REST_API_Log_API_Response', $entry->response );
		$this->assertSame( $post->post_content, $entry->response->body );
		$this->assertSame( array( 'X-Test' => '<i>yes</i>' ), $entry->response->headers );
	}

	/**
	 * Tests that an entry can be built from a post object, and that
	 * from_posts() builds one entry per post.
	 *
	 * @return void
	 */
	public function test_entry_from_posts() {

		$first  = $this->insert_entry();
		$second = $this->insert_entry();

		$entries = WP_REST_API_Log_Entry::from_posts( array( get_post( $first ), get_post( $second ) ) );

		$this->assertCount( 2, $entries );
		$this->assertContainsOnlyInstancesOf( 'WP_REST_API_Log_Entry', $entries );
		$this->assertSame( $first, $entries[0]->ID );
		$this->assertSame( $second, $entries[1]->ID );
		$this->assertSame( array(), WP_REST_API_Log_Entry::from_posts( array() ) );
	}

	/**
	 * Tests that an entry without a post is left empty.
	 *
	 * @return void
	 */
	public function test_empty_entry() {

		$entry = new WP_REST_API_Log_Entry();

		$this->assertNull( $entry->ID );
		$this->assertNull( $entry->request );
		$this->assertSame( '', $entry->_links['self']['href'] );

		// An ID that does not exist is treated the same way.
		$entry = new WP_REST_API_Log_Entry( PHP_INT_MAX );
		$this->assertNull( $entry->ID );
	}

	/**
	 * Tests reading the first term name for a taxonomy.
	 *
	 * @return void
	 */
	public function test_get_first_term_name() {

		$post_id = $this->insert_entry();
		$entry   = new WP_REST_API_Log_Entry( $post_id );

		$this->assertSame( 'PUT', $entry->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_METHOD ) );

		$other_post = self::factory()->post->create();
		$this->assertSame( '', $entry->get_first_term_name( $other_post, WP_REST_API_Log_DB::TAXONOMY_METHOD ) );
	}

	/**
	 * Tests the request and response objects on their own.
	 *
	 * @return void
	 */
	public function test_request_and_response_objects() {

		$post_id = $this->insert_entry();

		$request = new WP_REST_API_Log_API_Request( $post_id );
		$this->assertSame( '{"title":"<b>Hi</b>"}', $request->body );
		$this->assertSame( array( 'context' => '<em>edit</em>' ), $request->query_params );

		$response = new WP_REST_API_Log_API_Response( get_post( $post_id ) );
		$this->assertSame( array( 'X-Test' => '<i>yes</i>' ), $response->headers );

		// Without a post, nothing is loaded.
		$request = new WP_REST_API_Log_API_Request();
		$this->assertNull( $request->body );
		$this->assertNull( $request->headers );
		$this->assertSame( array(), $request->query_params );
		$this->assertSame( array(), $request->body_params );
	}

	/**
	 * Tests that a request without a stored body gets an empty string.
	 *
	 * @return void
	 */
	public function test_request_without_body_meta() {

		$post_id = self::factory()->post->create(
			array(
				'post_type' => WP_REST_API_Log_DB::POST_TYPE,
			)
		);

		$request = new WP_REST_API_Log_API_Request( $post_id );

		$this->assertSame( '', $request->body );
		$this->assertSame( array(), $request->headers );
	}

	/**
	 * Tests that the displayed fields are escaped.
	 *
	 * @return void
	 */
	public function test_esc_html_fields() {

		$entry = new WP_REST_API_Log_Entry( $this->insert_entry() );
		$entry = WP_REST_API_Log_API_Request_Response_Base::esc_html_fields( $entry );

		$this->assertSame( '&lt;em&gt;edit&lt;/em&gt;', $entry->request->query_params['context'] );
		$this->assertSame( array( 'text/html', '&lt;script&gt;' ), $entry->request->headers['accept'] );
		$this->assertSame( '&lt;i&gt;yes&lt;/i&gt;', $entry->response->headers['X-Test'] );

		// Body params are not in the default list of escaped fields.
		$this->assertSame( '<b>Hi</b>', $entry->request->body_params['title'] );
	}

	/**
	 * Tests that the escaped field lists can be filtered, including fields
	 * that hold a string rather than an array.
	 *
	 * @return void
	 */
	public function test_esc_html_fields_filters() {

		add_filter(
			'wp-rest-api-log-esc-html-request-fields',
			function ( $fields ) {
				$fields[] = 'body';
				return $fields;
			}
		);

		add_filter(
			'wp-rest-api-log-esc-html-response-fields',
			function ( $fields ) {
				$fields[] = 'body';
				return $fields;
			}
		);

		$entry                 = new WP_REST_API_Log_Entry( $this->insert_entry() );
		$entry->response->body = '<p>response</p>';

		$entry = WP_REST_API_Log_API_Request_Response_Base::esc_html_fields( $entry );

		$this->assertSame( '{&quot;title&quot;:&quot;&lt;b&gt;Hi&lt;/b&gt;&quot;}', $entry->request->body );
		$this->assertSame( '&lt;p&gt;response&lt;/p&gt;', $entry->response->body );
	}

	/**
	 * Tests the delete and routes response objects.
	 *
	 * @return void
	 */
	public function test_api_response_objects() {

		$delete = new WP_REST_API_Log_Delete_Response(
			(object) array(
				'args'             => array( 'older_than_seconds' => 60 ),
				'older_than_date'  => '2020-01-01 00:00:00',
				'records_affected' => 3,
			)
		);

		$this->assertSame( array( 'older_than_seconds' => 60 ), $delete->args );
		$this->assertSame( '2020-01-01 00:00:00', $delete->older_than_date );
		$this->assertSame( 3, $delete->records_affected );

		$empty_delete = new WP_REST_API_Log_Delete_Response();
		$this->assertSame( 0, $empty_delete->records_affected );
		$this->assertSame( '', $empty_delete->older_than_date );

		$routes = new WP_REST_API_Log_Routes_Response( array( '/wp/v2/posts', '/wp/v2/users' ) );
		$this->assertSame( array( '/wp/v2/posts', '/wp/v2/users' ), $routes->routes );
		$this->assertSame( 2, $routes->records_affected );

		$empty_routes = new WP_REST_API_Log_Routes_Response();
		$this->assertSame( array(), $empty_routes->routes );
		$this->assertSame( 0, $empty_routes->records_affected );
	}
}
