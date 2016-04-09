<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

$id = absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) );
$post_type_object = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

if ( ! current_user_can( $post_type_object->cap->read_post, $id ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'You are not allowed to read posts in this post type.', 'wp-rest-api-log' ) . '</p>',
		403
	);
}

if ( ! empty( $id ) ) {
	$entry = new WP_REST_API_Log_Entry( $id );
}

if ( empty( $entry->ID ) ) {
	wp_die(
		'<h1>' . esc_html_e( 'Invalid WP REST API Log Entry ID', 'wp-rest-api-log' ) . '</h1>',
		404
	);
}

?>
<div class="wrap wp-rest-api-log-entry" id="wp-rest-api-log-entry">

	

	<h1>Route: <?php echo esc_html( $entry->route ); ?></h1>

	<div id="poststuff">

		<div class="postbox request-headers">
			<h3 class="hndle"><span>Details</span></h3>

			<div class="inside collapsed">
				<ul>
					<li><?php esc_html_e( 'Date' ); ?>: <?php echo esc_html( $entry->time ); ?></li>
					<li><?php esc_html_e( 'Source', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->source ); ?></li>
					<li><?php esc_html_e( 'Method', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->method ); ?></li>
					<li><?php esc_html_e( 'Status', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->status ); ?></li>
					<li><?php esc_html_e( 'Elapsed Time', 'wp-rest-api-log' ); ?>: <?php echo esc_html( number_format( $entry->milliseconds ) ); ?>ms</li>
					<li><?php esc_html_e( 'Response Length', 'wp-rest-api-log' ); ?>: <?php echo esc_html( number_format( strlen( $entry->response->body ) ) ); ?></li>
					<li><?php esc_html_e( 'IP Address', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->ip_address ); ?></li>
				</ul>
			</div>
		</div>

		<div class="postbox request-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Request Headers', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside collapsed"><pre><code class="json"><?php echo json_encode( $entry->request->headers, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox querystring-parameters">
			<h3 class="hndle"><span><?php esc_html_e( 'Query Parameters', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside collapsed"><pre><code class="json"><?php echo json_encode( $entry->request->query_params, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox body-parameters">
			<h3 class="hndle"><span><?php esc_html_e( 'Body Parameters', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside collapsed"><pre><code class="json"><?php echo json_encode( $entry->request->body_params, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox response-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Response Headers', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside collapsed"><pre><code class="json"><?php echo json_encode( $entry->response->headers, JSON_PRETTY_PRINT ); ?></code></pre></div>
		</div>

		<div class="postbox response-body">
			<h3 class="hndle"><span><?php esc_html_e( 'Response', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside collapsed"><pre><code><?php echo esc_html( $entry->response->body ); ?></code></pre></div>
		</div>

	</div>

</div>
