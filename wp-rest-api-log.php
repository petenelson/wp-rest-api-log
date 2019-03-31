<?php
/**
 * Plugin Name: REST API Log
 * Description: Logs requests and responses for the REST API
 * Author: Pete Nelson
 * Author URI: https://petenelson.io
 * Version: 1.6.7
 * Plugin URI: https://github.com/petenelson/wp-rest-api-log
 * Text Domain: wp-rest-api-log
 * Domain Path: /languages
 * License: GPL2+
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! defined( 'WP_REST_API_LOG_VERSION' ) ) {
	define( 'WP_REST_API_LOG_VERSION', '1.6.6' );
}

if ( ! defined( 'WP_REST_API_LOG_ROOT' ) ) {
	define( 'WP_REST_API_LOG_ROOT', trailingslashit( dirname( __FILE__ ) ) );
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

$plugin_class_file = 'wp-rest-api-log';

$includes = array(
	'includes/class-' . $plugin_class_file . '-common.php',
	'includes/class-' . $plugin_class_file . '-db.php',
	'includes/class-' . $plugin_class_file . '-post-type.php',
	'includes/class-' . $plugin_class_file . '-taxonomies.php',
	'includes/class-' . $plugin_class_file . '-i18n.php',
	'includes/class-' . $plugin_class_file . '-controller.php',
	'includes/class-' . $plugin_class_file . '-request-response-base.php',
	'includes/class-' . $plugin_class_file . '-request.php',
	'includes/class-' . $plugin_class_file . '-response.php',
	'includes/class-' . $plugin_class_file . '-entry.php',
	'includes/class-' . $plugin_class_file . '-response-base.php',
	'includes/class-' . $plugin_class_file . '-delete-response.php',
	'includes/class-' . $plugin_class_file . '-routes-response.php',
	'includes/class-' . $plugin_class_file . '-elasticpress.php',
	'includes/class-' . $plugin_class_file . '-filters.php',
	'includes/class-' . $plugin_class_file . '.php',
	'includes/settings/class-' . $plugin_class_file . '-settings-base.php',
	'includes/settings/class-' . $plugin_class_file . '-settings-general.php',
	'includes/settings/class-' . $plugin_class_file . '-settings-routes.php',
	'includes/settings/class-' . $plugin_class_file . '-settings-elasticpress.php',
	'includes/settings/class-' . $plugin_class_file . '-settings-help.php',
	'includes/settings/class-' . $plugin_class_file . '-settings.php',
	'admin/class-' . $plugin_class_file . '-admin.php',
	'admin/class-' . $plugin_class_file . '-admin-list-table.php',
);

$class_base = 'WP_REST_API_Log';

$classes = array(
	$class_base . '_Common',
	$class_base . '_DB',
	$class_base . '_Post_Type',
	$class_base . '_i18n',
	$class_base . '_Controller',
	$class_base . '_Filters',
	$class_base . '',
	$class_base . '_Admin',
	$class_base . '_Admin_List_Table',
);


/* Include classes */
foreach ( $includes as $include ) {
	require_once WP_REST_API_LOG_PATH . $include;
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
foreach ( $classes as $class ) {
	$plugin = new $class();
	if ( method_exists( $class, 'plugins_loaded' ) ) {
		add_action( 'plugins_loaded', array( $plugin, 'plugins_loaded' ), 1 );
	}
}

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
	function() {
		require_once 'includes/class-wp-rest-api-log-activator.php';
		WP_REST_API_Log_Activator::activate();
	}
);
