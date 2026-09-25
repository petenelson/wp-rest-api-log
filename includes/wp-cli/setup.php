<?php
/**
 * Registers the plugin's WP-CLI commands.
 *
 * This file is included at global scope, so every variable it declares is
 * prefixed and unset once the commands have been registered.
 *
 * @package wp-rest-api-log
 */

$wp_rest_api_log_cli_commands = array(
	'class-wp-rest-api-log-wp-cli-log.php' => array(
		'command' => 'rest-api-log',
		'class'   => 'WP_REST_API_Log_WP_CLI_Log',
	),
);

foreach ( $wp_rest_api_log_cli_commands as $wp_rest_api_log_cli_file => $wp_rest_api_log_cli_command ) {
	require_once WP_REST_API_LOG_PATH . 'includes/wp-cli/' . $wp_rest_api_log_cli_file;
	WP_CLI::add_command( $wp_rest_api_log_cli_command['command'], $wp_rest_api_log_cli_command['class'] );
}

unset( $wp_rest_api_log_cli_commands, $wp_rest_api_log_cli_file, $wp_rest_api_log_cli_command );
