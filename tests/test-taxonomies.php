<?php
/**
 * Class WP_REST_API_Log_Test_Taxonomies
 *
 * @package wp-rest-api-log
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Taxonomies extends WP_UnitTestCase {

	/**
	 * Tests that the plugin's taxonomies are registered.
	 *
	 * @return void
	 */
	public function test_registered_taxonomies() {

		// Verify the taxonomies are registered.
		$taxonomies = array(
			WP_REST_API_Log_DB::TAXONOMY_METHOD,
			WP_REST_API_Log_DB::TAXONOMY_STATUS,
			WP_REST_API_Log_DB::TAXONOMY_SOURCE,
		);

		foreach ( $taxonomies as $taxonomy ) {
			$this->assertInstanceOf( '\WP_Taxonomy', get_taxonomy( $taxonomy ) );
		}
	}

	/**
	 * Tests the taxonomy arguments and labels.
	 *
	 * @return void
	 */
	public function test_taxonomy_args() {

		$taxonomy = get_taxonomy( WP_REST_API_Log_DB::TAXONOMY_METHOD );

		$this->assertFalse( $taxonomy->public );
		$this->assertFalse( $taxonomy->show_ui );
		$this->assertFalse( $taxonomy->hierarchical );
		$this->assertSame( array( WP_REST_API_Log_DB::POST_TYPE ), $taxonomy->object_type );

		// Bug: the plural and singular labels are swapped for all three
		// taxonomies ("Method" is the name and "Methods" the singular name).
		$this->assertSame( 'Method', $taxonomy->labels->name );
		$this->assertSame( 'Methods', $taxonomy->labels->singular_name );
	}

	/**
	 * Tests that the taxonomies and their arguments can be filtered.
	 *
	 * @return void
	 */
	public function test_taxonomy_filters() {

		add_filter(
			'wp-rest-api-log-custom-taxonomies',
			function ( $taxonomies ) {
				$taxonomies[ WP_REST_API_Log_DB::TAXONOMY_SOURCE ]['name'] = 'Filtered Sources';
				return $taxonomies;
			}
		);

		$filtered = array();
		add_filter(
			'wp-rest-api-log-register-taxonomy-args',
			function ( $args, $taxonomy ) use ( &$filtered ) {
				$filtered[] = $taxonomy;
				return $args;
			},
			10,
			2
		);

		WP_REST_API_Log_Taxonomies::register_custom_taxonomies();
		$label = get_taxonomy( WP_REST_API_Log_DB::TAXONOMY_SOURCE )->labels->name;

		// Put the original registration back for the other tests.
		remove_all_filters( 'wp-rest-api-log-custom-taxonomies' );
		remove_all_filters( 'wp-rest-api-log-register-taxonomy-args' );
		WP_REST_API_Log_Taxonomies::register_custom_taxonomies();

		$this->assertSame( 'Filtered Sources', $label );
		$this->assertSame(
			array( WP_REST_API_Log_DB::TAXONOMY_METHOD, WP_REST_API_Log_DB::TAXONOMY_STATUS, WP_REST_API_Log_DB::TAXONOMY_SOURCE ),
			$filtered
		);
		$this->assertSame( 10, has_action( 'init', array( 'WP_REST_API_Log_Taxonomies', 'register_custom_taxonomies' ) ) );
	}
}
