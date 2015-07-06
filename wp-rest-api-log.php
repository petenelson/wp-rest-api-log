<?php
/*
Plugin Name: WP REST API Log
Description: Logs requests and responses for the WP REST API
Author: Pete Nelson
Version: 1.0.0
*/

$plugin_class_file = 'wp-rest-api-log';

$includes = array(
	'includes/class-' . $plugin_class_file . '-common.php',
	'includes/class-' . $plugin_class_file . '-db.php',
	'includes/class-' . $plugin_class_file . '-i18n.php',
	'includes/class-' . $plugin_class_file . '-controller.php',
	'includes/class-' . $plugin_class_file . '.php',
	'admin/class-' . $plugin_class_file . '-admin.php',
);

$class_base = 'WP_REST_API_Log';

$classes = array(
	$class_base . '_Common',
	$class_base . '_DB',
	$class_base . '_i18n',
	$class_base . '_Controller',
	$class_base . '',
	$class_base . '_Admin',
);


// include classes
foreach ( $includes as $include ) {
	require_once plugin_dir_path( __FILE__ ) . $include;
}

// record the start time so we can log total millisecons
global $wp_rest_api_log_start;
$wp_rest_api_log_start = WP_REST_API_Log_Common::current_milliseconds();


// instantiate classes and hook into WordPress
foreach ( $classes as $class ) {
	$plugin = new $class();
	if ( method_exists( $class, 'plugins_loaded' ) ) {
		add_action( 'plugins_loaded', array( $plugin, 'plugins_loaded' ), 1 );
	}
}


// activation hook
register_activation_hook( __FILE__, function() {
	require_once 'includes/class-wp-rest-api-log-db.php';
	require_once 'includes/class-wp-rest-api-log-activator.php';
	WP_REST_API_Log_Activator::activate();
} );

