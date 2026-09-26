<?php
/**
 * Defines the plugin's runtime constants so PHPStan can resolve them in files
 * that are loaded conditionally rather than through the main plugin file.
 *
 * @package WP_REST_API_Log
 */

// Read the version from the main plugin file so this never drifts from it.
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Runs in PHPStan's bootstrap, where WordPress is not loaded.
$wp_rest_api_log_main_file = file_get_contents( __DIR__ . '/../wp-rest-api-log.php' );
preg_match( "/define\(\s*'WP_REST_API_LOG_VERSION',\s*'([^']+)'\s*\)/", $wp_rest_api_log_main_file, $wp_rest_api_log_version );

define( 'WP_REST_API_LOG_VERSION', $wp_rest_api_log_version[1] );
define( 'WP_REST_API_LOG_ROOT', __DIR__ . '/../' );
define( 'WP_REST_API_LOG_PATH', __DIR__ . '/../' );
define( 'WP_REST_API_LOG_URL', 'https://example.com/wp-content/plugins/wp-rest-api-log/' );
define( 'WP_REST_API_LOG_FILE', __DIR__ . '/../wp-rest-api-log.php' );
define( 'WP_REST_API_LOG_BASENAME', 'wp-rest-api-log/wp-rest-api-log.php' );
