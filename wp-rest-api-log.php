<?php
/**
 * Plugin Name: REST API Log
 * Description: Logs requests and responses for the REST API
 * Author: Pete Nelson
 * Author URI: https://petenelson.io
 * Version: 1.7.2
 * Plugin URI: https://github.com/petenelson/wp-rest-api-log
 * Text Domain: wp-rest-api-log
 * Domain Path: /languages
 * License: GPL2+
 * Requires: PHP 7.4
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! defined( 'WP_REST_API_LOG_VERSION' ) ) {
	define( 'WP_REST_API_LOG_VERSION', '1.7.2' );
}

if ( ! defined( 'WP_REST_API_LOG_ROOT' ) ) {
	define( 'WP_REST_API_LOG_ROOT', trailingslashit( __DIR__ ) );
}

if ( ! defined( 'WP_REST_API_LOG_PATH' ) ) {
	define( 'WP_REST_API_LOG_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'WP_REST_API_LOG_URL' ) ) {
	define( 'WP_REST_API_LOG_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'WP_REST_API_LOG_FILE' ) ) {
	define( 'WP_REST_API_LOG_FILE', __FILE__ );
}

if ( ! defined( 'WP_REST_API_LOG_BASENAME' ) ) {
	define( 'WP_REST_API_LOG_BASENAME', plugin_basename( WP_REST_API_LOG_FILE ) );
}

$wp_rest_api_log_class_file = 'wp-rest-api-log';

$wp_rest_api_log_includes = array(
	'includes/class-' . $wp_rest_api_log_class_file . '-common.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-db.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-post-type.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-taxonomies.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-i18n.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-controller.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-request-response-base.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-request.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-response.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-entry.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-response-base.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-delete-response.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-routes-response.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-elasticpress.php',
	'includes/class-' . $wp_rest_api_log_class_file . '-filters.php',
	'includes/class-' . $wp_rest_api_log_class_file . '.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings-base.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings-general.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings-routes.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings-elasticpress.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings-help.php',
	'includes/settings/class-' . $wp_rest_api_log_class_file . '-settings.php',
	'admin/class-' . $wp_rest_api_log_class_file . '-admin.php',
	'admin/class-' . $wp_rest_api_log_class_file . '-admin-list-table.php',
);

$wp_rest_api_log_class_base = 'WP_REST_API_Log';

$wp_rest_api_log_classes = array(
	$wp_rest_api_log_class_base . '_Common',
	$wp_rest_api_log_class_base . '_DB',
	$wp_rest_api_log_class_base . '_Post_Type',
	$wp_rest_api_log_class_base . '_i18n',
	$wp_rest_api_log_class_base . '_Controller',
	$wp_rest_api_log_class_base . '_Filters',
	$wp_rest_api_log_class_base . '',
	$wp_rest_api_log_class_base . '_Admin',
	$wp_rest_api_log_class_base . '_Admin_List_Table',
);


/* Include classes */
foreach ( $wp_rest_api_log_includes as $wp_rest_api_log_include ) {
	require_once WP_REST_API_LOG_PATH . $wp_rest_api_log_include;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WP_REST_API_LOG_PATH . 'includes/wp-cli/setup.php';
}

/* Record the start time so we can log total millisecons */
if ( class_exists( 'WP_REST_API_Log_Common' ) ) {
	global $wp_rest_api_log_start;
	$wp_rest_api_log_start = WP_REST_API_Log_Common::current_milliseconds();
}


/* Instantiate classes and hook into WordPress */
foreach ( $wp_rest_api_log_classes as $wp_rest_api_log_class ) {
	$wp_rest_api_log_plugin = new $wp_rest_api_log_class();
	if ( method_exists( $wp_rest_api_log_class, 'plugins_loaded' ) ) {
		add_action( 'plugins_loaded', array( $wp_rest_api_log_plugin, 'plugins_loaded' ), 1 );
	}
}

unset(
	$wp_rest_api_log_class_file,
	$wp_rest_api_log_includes,
	$wp_rest_api_log_include,
	$wp_rest_api_log_class_base,
	$wp_rest_api_log_classes,
	$wp_rest_api_log_class,
	$wp_rest_api_log_plugin
);

// Wire up hooks and filters in static classes.
WP_REST_API_Log_i18n::plugins_loaded();
WP_REST_API_Log::plugins_loaded();
WP_REST_API_Log_Settings::plugins_loaded();
WP_REST_API_Log_Settings_General::plugins_loaded();
WP_REST_API_Log_Settings_Routes::plugins_loaded();
WP_REST_API_Log_Settings_ElasticPress::plugins_loaded();
WP_REST_API_Log_Settings_Help::plugins_loaded();
WP_REST_API_Log_Post_Type::plugins_loaded();
WP_REST_API_Log_Taxonomies::plugins_loaded();
WP_REST_API_Log_Controller::plugins_loaded();
WP_REST_API_Log_ElasticPress::plugins_loaded();
WP_REST_API_Log_Admin::plugins_loaded();

/* Activation hook */
register_activation_hook(
	__FILE__,
	function () {
		require_once 'includes/class-wp-rest-api-log-activator.php';
		WP_REST_API_Log_Activator::activate();
	}
);
