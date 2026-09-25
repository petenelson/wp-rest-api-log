<?php
/**
 * Class WP_REST_API_Log_Test_Post_Type
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for the log entry custom post type registration.
 */
class WP_REST_API_Log_Test_Post_Type extends WP_UnitTestCase {

	use WP_REST_API_Log_Test_Hooks;

	/**
	 * Tests that the log entry post type is registered and is kept away from
	 * the front end.
	 *
	 * @return void
	 */
	public function test_registered_post_type() {

		$post_type = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

		$this->assertInstanceOf( '\WP_Post_Type', $post_type );

		// Log entries store full request and response bodies, so none of these
		// may be flipped without exposing them.
		$this->assertFalse( $post_type->publicly_queryable );
		$this->assertFalse( $post_type->public );
		$this->assertFalse( $post_type->show_in_rest );
		$this->assertTrue( $post_type->exclude_from_search );
	}

	/**
	 * Tests that a log entry cannot be read on the front end by requesting its
	 * post ID directly.
	 *
	 * @return void
	 */
	public function test_log_entry_is_not_publicly_queryable() {

		$post_id = self::factory()->post->create(
			array(
				'post_type'    => WP_REST_API_Log_DB::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => '/wp/v2/users/me/application-passwords',
				'post_content' => '{"password":"abcd efgh ijkl mnop"}',
			)
		);

		// Not viewable keeps entries out of oEmbed responses. WP 6.8+ also
		// blocks them through the "embeddable" arg, which defaults to "public".
		$this->assertFalse( is_post_type_viewable( WP_REST_API_Log_DB::POST_TYPE ) );
		$this->assertFalse( get_oembed_response_data( $post_id, 600 ) );

		// Simulate a real front-end request instead of using go_to(). go_to()
		// passes the query string to WP::main() as extra query vars, which
		// restores private vars such as post_type after core strips them for
		// post types that aren't publicly queryable.
		$query = array(
			'post_type' => WP_REST_API_Log_DB::POST_TYPE,
			'p'         => (string) $post_id,
		);

		$_GET                   = $query;
		$_SERVER['REQUEST_URI'] = '/?' . http_build_query( $query );

		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];
		$GLOBALS['wp']           = new WP();
		$GLOBALS['wp']->main();

		$_GET = array();

		$this->assertTrue( is_404() );
		$this->assertEmpty( $GLOBALS['wp_query']->posts );
	}

	/**
	 * Tests the post type arguments, which keep entries in the Tools menu
	 * behind their own capabilities.
	 *
	 * @return void
	 */
	public function test_post_type_args() {

		$args = WP_REST_API_Log_Post_Type::get_post_type_args();

		$this->assertSame( 'tools.php', $args['show_in_menu'] );
		$this->assertFalse( $args['map_meta_cap'] );
		$this->assertFalse( $args['query_var'] );
		$this->assertFalse( $args['rewrite'] );
		$this->assertSame( 'read_wp-rest-api-log', $args['capabilities']['read_post'] );
		$this->assertSame( 'delete_wp-rest-api-log', $args['capabilities']['delete_post'] );
		$this->assertSame( 'create_wp-rest-api-logs', $args['capabilities']['create_posts'] );
		$this->assertSame( 'REST API Log Entries', $args['labels']['name'] );
		$this->assertSame( WP_REST_API_Log_Post_Type::get_post_type_labels(), $args['labels'] );
	}

	/**
	 * Tests that the post type labels and arguments can be filtered.
	 *
	 * @return void
	 */
	public function test_post_type_filters() {

		add_filter(
			'wp-rest-api-log-post-type-labels',
			function ( $labels ) {
				$labels['name'] = 'Filtered Entries';
				return $labels;
			}
		);

		add_filter(
			'wp-rest-api-log-register-post-type',
			function ( $args ) {
				$args['show_in_menu'] = false;
				return $args;
			}
		);

		WP_REST_API_Log_Post_Type::register_custom_post_types();
		$post_type = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

		// Put the original registration back for the other tests.
		remove_all_filters( 'wp-rest-api-log-post-type-labels' );
		remove_all_filters( 'wp-rest-api-log-register-post-type' );
		WP_REST_API_Log_Post_Type::register_custom_post_types();

		$this->assertSame( 'Filtered Entries', $post_type->labels->name );
		$this->assertFalse( $post_type->show_in_menu );
		$this->assert_registers_hooks(
			array( 'WP_REST_API_Log_Post_Type', 'plugins_loaded' ),
			array(
				array( 'init', array( 'WP_REST_API_Log_Post_Type', 'register_custom_post_types' ), 10 ),
			)
		);
	}
}
