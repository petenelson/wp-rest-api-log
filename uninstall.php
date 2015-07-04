<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$able_name = $wpdb->prefix . 'wp_rest_api_log';
$wpdb->query( "drop table $table_name");
