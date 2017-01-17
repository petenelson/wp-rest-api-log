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
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/v2/' );
		$this->assertEquals( '^/wp/v2/?$', $route_regex );

		// Wildcard matches.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/*/' );
		$this->assertEquals( '^/wp/.*/?$', $route_regex );

		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '/wp/v2/*' );
		$this->assertEquals( '^/wp/v2/.*/?$', $route_regex );

		// Regex should have no changes.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '^/wp/v2/.*' );
		$this->assertEquals( '^/wp/v2/.*', $route_regex );
		
		// This is not treated as regex, so it get mangled.
		$route_regex = WP_REST_API_Log_Filters::route_to_regex( '.*/wp/v2/$' );
		$this->assertEquals( '^..*/wp/v2/$/?$', $route_regex );
	}

	public function test_should_log_route() {

	}

}
