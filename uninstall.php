<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
		$wpdb->prefix . 'wp_rest_api_log',
		$wpdb->prefix . 'wp_rest_api_logmeta',
	);

foreach ( $tables as $table_name ) {
	$wpdb->query( "drop table $table_name");
}
