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

// The custom tables log entries can be stored in, created for each site that
// turned on "Use Custom Tables". These mirror
// WP_REST_API_Log_DB::get_custom_table_names(), since the plugin isn't loaded.
// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Existing filter name.
$wp_rest_api_log_custom_prefix = apply_filters( 'wp-rest-api-log-custom-table-prefix', 'rest_api_log_' );
$wp_rest_api_log_site_ids      = is_multisite() ? get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
) : array( get_current_blog_id() );

foreach ( $wp_rest_api_log_site_ids as $wp_rest_api_log_site_id ) {
	foreach ( array( 'posts', 'postmeta', 'terms', 'termmeta', 'term_taxonomy', 'term_relationships' ) as $wp_rest_api_log_table ) {
		$wp_rest_api_log_tables[] = $wpdb->get_blog_prefix( $wp_rest_api_log_site_id ) . $wp_rest_api_log_custom_prefix . $wp_rest_api_log_table;
	}
}

foreach ( $wp_rest_api_log_tables as $wp_rest_api_log_table_name ) {
	// Table names cannot be passed through $wpdb->prepare(), and these are
	// built from $wpdb->prefix rather than from user input.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "drop table if exists $wp_rest_api_log_table_name" );
}


// The plugin is not loaded during uninstall, so the option names cannot be
// read from the settings classes and are listed here instead.
$wp_rest_api_log_options = array(
	'wp-rest-api-log-meta-dbversion',
	'wp-rest-api-log-entries-dbversion',
	'wp-rest-api-log-settings-general',
	'wp-rest-api-log-settings-routes',
	'wp-rest-api-log-settings-headers',
	'wp-rest-api-log-settings-elasticpress',
	'wp-rest-api-log-settings-advanced',
	'wp-rest-api-log-plugin-activated',
	'wp-rest-api-log-db-notice-dismissed',
);

foreach ( $wp_rest_api_log_options as $wp_rest_api_log_option ) {
	delete_option( $wp_rest_api_log_option );
}

unset(
	$wp_rest_api_log_tables,
	$wp_rest_api_log_table_name,
	$wp_rest_api_log_custom_prefix,
	$wp_rest_api_log_site_ids,
	$wp_rest_api_log_site_id,
	$wp_rest_api_log_table,
	$wp_rest_api_log_options,
	$wp_rest_api_log_option
);
