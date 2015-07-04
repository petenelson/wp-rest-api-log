<?php
/*
Plugin Name: WP REST API Log Filters
Description: Custom filters for my logging plugin
Author: Pete Nelson
Version: 1.0.0
*/


add_filter( 'wp-rest-api-log-can-view-entries', function( $can_view ) {

	if ( 'helloworld' === $_REQUEST['api-log-key'] ) {
		$can_view = true;
	}

	return $can_view;
} );