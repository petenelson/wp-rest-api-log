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

				// Add the end of the match.
				$route_filter .= '$';

				// Convert backslash to literals.
				$route_filter = str_replace( '/', "\/", $route_filter );
			}

		}

		return $route_filter;
	}

	/**
	 * Determines if the supplied route can be logged.
	 *
	 * @param  string $route Route (ex: /wp/v2).
	 * @return bool
	 */
	static public function can_log_route( $route ) {

		// Get the filter mode.
		$route_logging_mode = apply_filters( 'wp-rest-api-log-setting-get', 'routes', 'route-log-matching-mode' );

		// If no logging mode is set, we can log the route.
		if ( empty( $route_logging_mode ) ) {
			return true;
		}

		// Get the route filters.
		$route_filters = apply_filters( 'wp-rest-api-log-setting-get', 'routes', 'route-filters' );
		$route_filters = array_values( array_map( 'trim', explode( "\n", $route_filters ) ) );

		// If we're set to exclude matching filters, but we have no filters,
		// then the route can be logged
		if ( 'exclude_matches' === $route_logging_mode && empty( $route_filters ) ) {
			return true;
		}

		// Loop through the filters and apply each one to the route.
		foreach( $route_filters as $route_filter ) {
			if ( empty( $route_filter  ) ) {
				continue;
			}

			$regex = self::route_to_regex( $route_filter );

			//preg_match() returns 1 if the pattern matches given subject,
			//0 if it does not, or FALSE if an error occurred.
			$match = preg_match( '/' . $regex . '/', $route );

			// We can log this if the mode is set to log only matches.
			if ( 1 === $match && 'log_matches' === $route_logging_mode ) {
				return true;
			}

			// We cannot log this if the mode is set to exclude matches.
			if ( 1 === $match && 'exclude_matches' === $route_logging_mode ) {
				return false;
			}
		}

		// At this point, we can only log the match if we're set to exclude
		// mode and the loop above did not find a match.
		return 'exclude_matches' === $route_logging_mode;
	}
}
