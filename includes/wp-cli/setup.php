<?php

$commands = array(
	'class-wp-rest-api-log-wp-cli-log.php'    => array( 'command' => 'rest-api-log', 'class' => 'WP_REST_API_Log_WP_CLI_Log' ),
	);

foreach ( $commands as $file => $command_info ) {
	require_once WP_REST_API_LOG_PATH . 'includes/wp-cli/' . $file;
	extract( $command_info );
	WP_CLI::add_command( $command, $class );	
}
