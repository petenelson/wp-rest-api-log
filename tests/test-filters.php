<?php
/**
 * Class WP_REST_API_Log_Test_Filters
 *
 * @package 
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Filters extends WP_UnitTestCase {


	public function test_convert_route_filter() {

		// Just a path, adds start and end.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/v2' );
		$this->assertEquals( "^\/wp\/v2$", $route_regex );

		// Wildcard matches.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/*' );
		$this->assertEquals( "^\/wp\/.*$", $route_regex );

		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/v2/*' );
		$this->assertEquals( "^\/wp\/v2\/.*$", $route_regex, '/wp/v2/*' );

		// Regex should have no changes.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '^/wp/v2/.*' );
		$this->assertEquals( '^/wp/v2/.*', $route_regex );
		
		// This is not treated as regex, so it get mangled.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '.*/wp/v2/$' );
		$this->assertEquals( "^..*\/wp\/v2\/$$", $route_regex );
	}

	public function test_filter_modes() {
		$modes = WP_REST_API_Log_Filters::filter_modes();
		$this->assertArrayHasKey( '',                $modes );
		$this->assertArrayHasKey( 'log_matches',     $modes );
		$this->assertArrayHasKey( 'exclude_matches', $modes );
	}

	public function test_route_logging_all_routes() {

		$option_key = 'wp-rest-api-log-settings-routes';

		// Set the options to log everything.
		$option_value = array(
			'route-log-matching-mode' => '',
			);

		update_option( 'wp-rest-api-log-settings-routes', $option_value );

		// Make sure we can log any route.
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/wp/v2' ) );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/wp/v2/posts' ) );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/wp/v2/users' ) );

	}


	public function test_route_logging_only_matched_routes() {

		$option_key = 'wp-rest-api-log-settings-routes';

		// Set the options to log only matching routes.
		$option_value = array(
			'route-log-matching-mode' => 'log_matches',
			'route-filters' =>
"/wp/v2
/route/wildcard*
^\/route\/regex-exact$
^\/route\/regex-wildcard.*$"
			);

		update_option( 'wp-rest-api-log-settings-routes', $option_value );

		// Exact match.
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/wp/v2' ), '/wp/v2' );

		// Basic wildcard.
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/wildcard-route' ), '/route/wildcard-route' );

		// Exact regex
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/regex-exact' ), '/route/regex-exact' );

		// Wildcard regex
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/regex-wildcard/test' ), '/route/regex-wildcard/test' );

		// Test non-matching routes.
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/some/route' ), '/some/route' );
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/wildercard' ), '/route/wildercard' );
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/exact-regex' ), '/route/exact-regex' );
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/regexwildcard' ), '/route/regexwildcard' );
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/wp/v2/posts' ), '/wp/v2/posts' );

	}

	public function test_route_logging_excluded_matched_routes() {

		$option_key = 'wp-rest-api-log-settings-routes';

		// Set the options to log only matching routes.
		$option_value = array(
			'route-log-matching-mode' => 'exclude_matches',
			'route-filters' =>
"/wp/v2
/route/wildcard*
^\/route\/regex-exact$
^\/route\/regex-wildcard.*$"
			);

		update_option( 'wp-rest-api-log-settings-routes', $option_value );

		// Exact match.
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/wp/v2' ), '/wp/v2' );

		// Basic wildcard.
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/wildcard-route' ), '/route/wildcard-route' );

		// Exact regex
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/regex-exact' ), '/route/regex-exact' );

		// Wildcard regex
		$this->assertFalse( WP_REST_API_Log_Filters::can_log_route( '/route/regex-wildcard/test' ), '/route/regex-wildcard/test' );

		// Test non-matching routes, should all be logged.
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/some/route' ), '/some/route' );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/wildercard' ), '/route/wildercard' );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/exact-regex' ), '/route/exact-regex' );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/route/regexwildcard' ), '/route/regexwildcard' );
		$this->assertTrue( WP_REST_API_Log_Filters::can_log_route( '/wp/v2/posts' ), '/wp/v2/posts' );

	}

}
