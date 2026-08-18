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
}
