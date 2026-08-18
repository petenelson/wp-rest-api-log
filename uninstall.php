<?php
/**
 * Removes the plugin's custom tables and options when it is uninstalled.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wp_rest_api_log_tables = array(
	$wpdb->prefix . 'wp_rest_api_log',
	$wpdb->prefix . 'wp_rest_api_logmeta',
);

foreach ( $wp_rest_api_log_tables as $wp_rest_api_log_table_name ) {
	// Table names cannot be passed through $wpdb->prepare(), and these are
	// built from $wpdb->prefix rather than from user input.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "drop table if exists $wp_rest_api_log_table_name" );
}


$wp_rest_api_log_options = array(
	'wp-rest-api-log-meta-dbversion',
	'wp-rest-api-log-entries-dbversion',
	'wp-rest-api-log-settings-general',
);

foreach ( $wp_rest_api_log_options as $wp_rest_api_log_option ) {
	delete_option( $wp_rest_api_log_option );
}

unset(
	$wp_rest_api_log_tables,
	$wp_rest_api_log_table_name,
	$wp_rest_api_log_options,
	$wp_rest_api_log_option
);
