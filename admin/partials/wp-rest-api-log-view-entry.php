<?php

$id = absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) );

if ( ! empty( $id ) ) {
	$entry = new WP_REST_API_Log_Entry( $id );
}

if ( empty( $entry->ID ) ) {
	wp_die( __( 'Invalid entry ID', WP_REST_API_Log_Common::TEXT_DOMAIN ) );
} else {

	wp_enqueue_script( $this->plugin_name() .'-highlight-js' );
	wp_enqueue_style( $this->plugin_name() . '-highlight-css' );

	wp_enqueue_script( $this->plugin_name() );
	wp_enqueue_style( $this->plugin_name() );

}


?>
<div class="wrap wp-rest-api-log-entry">

	<h1><?php echo esc_html( $entry->route ); ?></h1>

	<div id="poststuff">

		<div class="postbox request-headers">
			<h3 class="hndle"><span>Details</span></h3>

			<div class="inside collapsed">
				<ul>
					<li><?php esc_html_e( 'Date' ); ?>: <?php echo esc_html( $entry->time ); ?></li>
					<li><?php esc_html_e( 'Method', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?>: <?php echo esc_html( $entry->method ); ?></li>
					<li><?php esc_html_e( 'Status', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?>: <?php echo esc_html( $entry->status ); ?></li>
					<li><?php esc_html_e( 'Elapsed Time', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?>: <?php echo esc_html( number_format( $entry->milliseconds ) ); ?>ms</li>
					<li><?php esc_html_e( 'Response Length', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?>: <?php echo esc_html( number_format( strlen( $entry->response->body ) ) ); ?></li>
					<li><?php esc_html_e( 'IP Address', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?>: <?php echo esc_html( $entry->ip_address ); ?></li>
				</ul>
			</div>
		</div>

		<div class="postbox request-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Request Headers', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo json_encode( $entry->request->headers, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox querystring-parameters">
			<h3 class="hndle"><span><?php esc_html_e( 'Query Parameters', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo json_encode( $entry->request->query_params, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox body-parameters">
			<h3 class="hndle"><span><?php esc_html_e( 'Body Parameters', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo json_encode( $entry->request->body_params, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox response-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Response Headers', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo json_encode( $entry->response->headers, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox response-body">
			<h3 class="hndle"><span><?php esc_html_e( 'Response', WP_REST_API_Log_Common::TEXT_DOMAIN ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo $entry->response->body; ?></code></pre></div>
		</div>

	</div>

</div>