<?php
/**
 * Class WP_REST_API_Log_Test_Headers
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for redacting sensitive header values.
 */
class WP_REST_API_Log_Test_Headers extends WP_UnitTestCase {

	/**
	 * Option name storing the Headers tab settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp-rest-api-log-settings-headers';

	/**
	 * Removes the Headers settings so each test starts from a known state.
	 *
	 * @return void
	 */
	public function tear_down() {
		delete_option( self::OPTION_KEY );
		parent::tear_down();
	}

	/**
	 * Tests that header names are normalized for comparison.
	 *
	 * @return void
	 */
	public function test_normalize_header_name() {

		$headers = WP_REST_API_Log_Headers::instance();

		$this->assertEquals( 'x_wp_nonce', $headers->normalize_header_name( 'X-WP-Nonce' ) );
		$this->assertEquals( 'x_wp_nonce', $headers->normalize_header_name( 'x_wp_nonce' ) );
		$this->assertEquals( 'x_wp_nonce', $headers->normalize_header_name( 'X_WP_NONCE' ) );
		$this->assertEquals( 'authorization', $headers->normalize_header_name( '  Authorization  ' ) );
	}

	/**
	 * Tests that the defaults are used until the Headers tab has been saved.
	 *
	 * @return void
	 */
	public function test_default_redacted_header_names() {

		$headers = WP_REST_API_Log_Headers::instance();

		$request_headers = $headers->get_redacted_header_names( 'request' );
		$this->assertContains( 'authorization', $request_headers );
		$this->assertContains( 'cookie', $request_headers );
		$this->assertContains( 'x_wp_nonce', $request_headers );

		$response_headers = $headers->get_redacted_header_names( 'response' );
		$this->assertContains( 'set_cookie', $response_headers );
		$this->assertNotContains( 'cookie', $response_headers );
	}

	/**
	 * Tests that an empty saved setting is honored instead of being replaced
	 * by the defaults.
	 *
	 * @return void
	 */
	public function test_empty_redacted_header_names() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-request-headers'  => '',
				'redacted-response-headers' => '',
			)
		);

		$headers = WP_REST_API_Log_Headers::instance();

		$this->assertEmpty( $headers->get_redacted_header_names( 'request' ) );
		$this->assertEmpty( $headers->get_redacted_header_names( 'response' ) );

		$request_headers = array(
			'authorization' => array( 'Bearer abc123' ),
		);

		$this->assertEquals( $request_headers, $headers->redact_headers( $request_headers, 'request' ) );
	}

	/**
	 * Tests that request header values are redacted.
	 *
	 * @return void
	 */
	public function test_redact_request_headers() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-request-headers' => "Authorization\nCookie",
			)
		);

		$headers = WP_REST_API_Log_Headers::instance();

		$redacted = $headers->redact_headers(
			array(
				'authorization' => array( 'Bearer abc123' ),
				'cookie'        => array( 'wordpress_logged_in=secret' ),
				'content_type'  => array( 'application/json' ),
			),
			'request'
		);

		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $redacted['authorization'] );
		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $redacted['cookie'] );
		$this->assertEquals( array( 'application/json' ), $redacted['content_type'] );

		// The header names are still logged.
		$this->assertEquals( array( 'authorization', 'cookie', 'content_type' ), array_keys( $redacted ) );
	}

	/**
	 * Tests that response header values are redacted, including when the
	 * header name uses dashes rather than underscores.
	 *
	 * @return void
	 */
	public function test_redact_response_headers() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-response-headers' => 'set-cookie',
			)
		);

		$headers = WP_REST_API_Log_Headers::instance();

		$redacted = $headers->redact_headers(
			array(
				'Set-Cookie'   => 'wordpress_logged_in=secret; path=/',
				'Content-Type' => 'application/json',
			),
			'response'
		);

		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $redacted['Set-Cookie'] );
		$this->assertEquals( 'application/json', $redacted['Content-Type'] );
	}

	/**
	 * Tests that the list of redacted headers can be filtered per request.
	 *
	 * @return void
	 */
	public function test_redacted_header_names_filter() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-request-headers' => 'Authorization',
			)
		);

		$request = array(
			'headers' => array(
				'user_agent' => array( 'PHPUnit' ),
			),
		);

		$callback = function ( $names, $type, $request_or_response ) use ( $request ) {
			$this->assertEquals( 'request', $type );
			$this->assertEquals( $request, $request_or_response );

			$names[] = 'user_agent';
			return $names;
		};

		add_filter( 'wp-rest-api-log-redacted-header-names', $callback, 10, 3 );

		$redacted = WP_REST_API_Log_Headers::instance()->redact_headers( $request['headers'], 'request', $request );

		remove_filter( 'wp-rest-api-log-redacted-header-names', $callback, 10 );

		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $redacted['user_agent'] );
	}

	/**
	 * Tests that the replacement text can be filtered.
	 *
	 * @return void
	 */
	public function test_redacted_header_value_filter() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-request-headers' => 'Authorization',
			)
		);

		$callback = function ( $redacted, $name, $type ) {
			$this->assertEquals( 'authorization', $name );
			$this->assertEquals( 'request', $type );

			return str_replace( array( '[', ']' ), array( '<', '>' ), $redacted );
		};

		add_filter( 'wp-rest-api-log-redacted-header-value', $callback, 10, 3 );

		$redacted = WP_REST_API_Log_Headers::instance()->redact_headers(
			array(
				'authorization' => array( 'Bearer abc123' ),
			),
			'request'
		);

		remove_filter( 'wp-rest-api-log-redacted-header-value', $callback, 10 );

		$this->assertEquals( '<REDACTED>', $redacted['authorization'] );
	}

	/**
	 * Tests that the textareas show the defaults until the tab has been saved,
	 * so the screen matches what is actually being redacted.
	 *
	 * @return void
	 */
	public function test_textarea_falls_back_to_defaults() {

		$args = array(
			'key'     => self::OPTION_KEY,
			'name'    => 'redacted-request-headers',
			'default' => WP_REST_API_Log_Settings_Headers::get_default_settings()['redacted-request-headers'],
		);

		ob_start();
		WP_REST_API_Log_Settings_Headers::settings_textarea( $args );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Authorization', $html );
		$this->assertStringContainsString( 'Cookie', $html );

		// A deliberately emptied textarea stays empty.
		update_option( self::OPTION_KEY, array( 'redacted-request-headers' => '' ) );

		ob_start();
		WP_REST_API_Log_Settings_Headers::settings_textarea( $args );
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'Authorization', $html );
	}

	/**
	 * Tests that headers are redacted when a log entry is inserted.
	 *
	 * @return void
	 */
	public function test_headers_are_redacted_on_insert() {

		update_option(
			self::OPTION_KEY,
			array(
				'redacted-request-headers'  => 'Authorization',
				'redacted-response-headers' => 'Set-Cookie',
			)
		);

		$db = new WP_REST_API_Log_DB();

		$post_id = $db->insert(
			array(
				'route'    => '/wp/v2/posts',
				'request'  => array(
					'body'    => '',
					'headers' => array(
						'authorization' => array( 'Bearer abc123' ),
						'content_type'  => array( 'application/json' ),
					),
				),
				'response' => array(
					'body'    => '',
					'headers' => array(
						'Set-Cookie'   => 'wordpress_logged_in=secret',
						'Content-Type' => 'application/json',
					),
				),
			)
		);

		$this->assertNotEmpty( $post_id );

		$entry = new WP_REST_API_Log_Entry( $post_id );

		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $entry->request->headers['authorization'] );
		$this->assertEquals( 'application/json', $entry->request->headers['content_type'] );
		$this->assertEquals( WP_REST_API_Log_Headers::REDACTED, $entry->response->headers['Set-Cookie'] );
		$this->assertEquals( 'application/json', $entry->response->headers['Content-Type'] );
	}
}
