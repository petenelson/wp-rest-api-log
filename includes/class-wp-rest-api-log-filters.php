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





		return $route_filter;
	}
}
