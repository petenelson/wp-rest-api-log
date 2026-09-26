<?php
/**
 * Class WP_REST_API_Log_Test_Common
 *
 * @package wp-rest-api-log
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Common extends WP_UnitTestCase {

	/**
	 * Make sure valid methods returns results
	 */
	public function test_valid_methods() {
		$valid_methods = WP_REST_API_Log_Common::valid_methods();
		$this->assertTrue( ! empty( $valid_methods ) );
		$this->assertContains( 'GET', $valid_methods );
		$this->assertContains( 'OPTIONS', $valid_methods );
	}

	/**
	 * Test that GET and OPTIONS are valid methods
	 */
	public function test_valid_method() {
		$valid_methods = WP_REST_API_Log_Common::valid_methods();
		$this->assertTrue( WP_REST_API_Log_Common::is_valid_method( 'GET' ) );
		$this->assertTrue( WP_REST_API_Log_Common::is_valid_method( 'OPTIONS' ) );
	}

	/**
	 * Tests that invalid methods are rejected and that both method filters
	 * are applied.
	 *
	 * @return void
	 */
	public function test_method_filters() {

		$this->assertFalse( WP_REST_API_Log_Common::is_valid_method( 'TRACE' ) );
		$this->assertFalse( WP_REST_API_Log_Common::is_valid_method( 'get' ) );

		add_filter(
			'wp-rest-api-log-valid-methods',
			function ( $methods ) {
				$methods[] = 'TRACE';
				return $methods;
			}
		);

		$this->assertTrue( WP_REST_API_Log_Common::is_valid_method( 'TRACE' ) );

		add_filter( 'wp-rest-api-log-is-method-valid', '__return_false' );
		$this->assertFalse( WP_REST_API_Log_Common::is_valid_method( 'GET' ) );
	}

	/**
	 * Tests converting microtime() output to milliseconds.
	 *
	 * @return void
	 */
	public function test_microtime_to_milliseconds() {

		$this->assertEqualsWithDelta( 1500000000500.0, WP_REST_API_Log_Common::microtime_to_milliseconds( '0.50000000 1500000000' ), 0.001 );

		$before = microtime( true ) * 1000;
		$now    = WP_REST_API_Log_Common::current_milliseconds();
		$after  = microtime( true ) * 1000;

		$this->assertGreaterThanOrEqual( floor( $before ), $now );
		$this->assertLessThanOrEqual( ceil( $after ), $now );
	}

	/**
	 * Tests reading a query string parameter with HTML removed.
	 *
	 * @return void
	 */
	public function test_get_string_query_param() {

		$_GET = array( 'tab' => '<b>routes</b>' );

		$this->assertSame( 'routes', WP_REST_API_Log_Common::get_string_query_param( 'tab' ) );
		$this->assertNull( WP_REST_API_Log_Common::get_string_query_param( 'missing' ) );

		$_GET = array();

		$this->assertSame(
			array(
				'filter'  => FILTER_CALLBACK,
				'options' => '\wp_strip_all_tags',
			),
			WP_REST_API_Log_Common::filter_strip_all_tags()
		);
	}

	/**
	 * Tests the taxonomy dropdown used to filter the list of entries.
	 *
	 * @return void
	 */
	public function test_dropdown_terms() {

		ob_start();
		WP_REST_API_Log_Common::dropdown_terms( 'no-such-taxonomy' );
		$this->assertSame( '', ob_get_clean() );

		$db = new WP_REST_API_Log_DB();
		$db->insert(
			array(
				'route'  => '/a',
				'method' => 'POST',
			)
		);
		$db->insert(
			array(
				'route'  => '/b',
				'method' => 'POST',
			)
		);
		$db->insert(
			array(
				'route'  => '/c',
				'method' => 'GET',
			)
		);

		// The selected term defaults to the query string.
		$_GET = array( WP_REST_API_Log_DB::TAXONOMY_METHOD => 'get' );

		ob_start();
		WP_REST_API_Log_Common::dropdown_terms( WP_REST_API_Log_DB::TAXONOMY_METHOD );
		$html = ob_get_clean();

		$_GET = array();

		$this->assertStringContainsString( '<select name="wp-rest-api-log-method" id="wp-rest-api-log-method">', $html );
		// The "all items" label defaults to the taxonomy's all_items label.
		$this->assertStringContainsString( '<option value="">Method</option>', $html );

		// Bug: the screen reader label uses the taxonomy's filter_by_item
		// label, which WordPress only sets for hierarchical taxonomies. The
		// plugin's taxonomies are not hierarchical, so the label is empty.
		$this->assertMatchesRegularExpression( '/<label class="screen-reader-text" for="wp-rest-api-log-method">\s*<\/label>/', $html );

		// Terms are ordered by count, with the count shown.
		$this->assertMatchesRegularExpression( '/value="post"\s+>POST \(2\)<\/option>.*value="get"\s+selected=\'selected\'\s+>GET \(1\)<\/option>/s', $html );

		// A selected term and label can be passed in.
		ob_start();
		WP_REST_API_Log_Common::dropdown_terms(
			WP_REST_API_Log_DB::TAXONOMY_METHOD,
			array(
				'selected'  => 'post',
				'all_label' => 'Any method',
			)
		);
		$html = ob_get_clean();

		$this->assertStringContainsString( '<option value="">Any method</option>', $html );
		$this->assertMatchesRegularExpression( '/value="post"\s+selected=\'selected\'/', $html );
	}
}
