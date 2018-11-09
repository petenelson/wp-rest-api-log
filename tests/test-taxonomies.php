<?php
/**
 * Class WP_REST_API_Log_Test_Taxonomies
 *
 * @package 
 */

/**
 * Sample test case.
 */
class WP_REST_API_Log_Test_Taxonomies extends WP_UnitTestCase {

	function test_registered_taxonomies() {

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

