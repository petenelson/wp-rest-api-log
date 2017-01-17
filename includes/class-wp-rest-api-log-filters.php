<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

class WP_REST_API_Log_Filters {

	/**
	 * Returns a list of filtering modes.
	 *
	 * @return array
	 */
	static public function filter_modes() {
		return array(
			''                   => __( 'All', 'wp-rest-api-log' ),
			'log_matches'        => __( 'Only Matching Filters', 'wp-rest-api-log' ),
			'exclude_matches'    => __( 'Exclude Matching Filters', 'wp-rest-api-log' ),
			);
	}


	/**
	 * Converts a route filter into regex pattern.
	 *
	 * @param  string $route_filter Route filter, may include * wildcards.
	 * @return string
	 */
	public static function route_to_regex( $route_filter ) {

		if ( ! empty( $route_filter ) ) {

			// If it starts with a carat, treat it as regex and
			// make no changes.
			if ( '^' === substr( $route_filter, 0, 1 ) ) {
				return $route_filter;
			} else {

				// Replace wildcard with regex wildcard.
				$route_filter = str_replace( '*', '.*', $route_filter );

				// Add the start of the match.
				$route_filter = '^' . $route_filter;

				// Add a trailing slash.
				$route_filter = trailingslashit( $route_filter );

				// Add a flag for zero or one trailing slashes.
				$route_filter .= '?';

				// Add the end of the match.
				$route_filter .= '$';
			}

		}

		return $route_filter;
	}

	static public function can_log_route( $route ) {

		// Get the filter mode.

		// Get the route filters.

	}
}
