<?php
/**
 * Defines the plugin's runtime constants so PHPStan can resolve them in files
 * that are loaded conditionally rather than through the main plugin file.
 *
 * @package WP_REST_API_Log
 */

define( 'WP_REST_API_LOG_VERSION', '1.7.2' );
define( 'WP_REST_API_LOG_ROOT', __DIR__ . '/../' );
define( 'WP_REST_API_LOG_PATH', __DIR__ . '/../' );
define( 'WP_REST_API_LOG_URL', 'https://example.com/wp-content/plugins/wp-rest-api-log/' );
define( 'WP_REST_API_LOG_FILE', __DIR__ . '/../wp-rest-api-log.php' );
define( 'WP_REST_API_LOG_BASENAME', 'wp-rest-api-log/wp-rest-api-log.php' );
