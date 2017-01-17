<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

class WP_REST_API_Log_Filters {


	/**
	 * Converts a route filter into regex pattern.
	 *
	 * @param  string $route_filter Route filter, may include * wildcards.
	 * @return string
	 */
	public static function route_to_regex( $route_filter ) {

		if ( ! empty( $route_filter ) ) {
			// Replace wildcard characters with regex.
			$route_filter = str_replace( '*', '.*', $route_filter );

			// Add a trailing slash if it doesn't end with one.
			if ( '/' !== substr( $route_filter, strlen( $route_filter ) - 1 ) ) {
				$route_filter .= '/';
			}

			// Add a zero or one match for the trailing slash.
			$route_filter .= '?';

			// Add regex start and end params.
			$route_filter = '^' . $route_filter . '$';
		}

		return $route_filter;
	}
}
