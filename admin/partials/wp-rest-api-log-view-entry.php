<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

$id = absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) );
$post_type_object = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

if ( ! current_user_can( $post_type_object->cap->read_post, $id ) ) {
	wp_die(
		'<h1>' . esc_html__( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . esc_html__( 'You are not allowed to read posts in this post type.', 'wp-rest-api-log' ) . '</p>',
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

// HTML encode some of the values for display in the admin partial.
$entry = WP_REST_API_Log_API_Request_Response_Base::esc_html_fields( $entry );

$entry = apply_filters( 'wp-rest-api-log-display-entry', $entry );

$body_content = ! empty( $entry->request->body ) ? $entry->request->body : '';

if ( 'ElasticPress' === $entry->source ) {
	// These request bodies are base64 encoded JSON.
	if ( ! empty( $body_content ) ) {
		$body_object = json_decode( base64_decode( $body_content ) );
		$body_content = '';
	}
}

$json_display_options = array(
	'request' => array(
		'headers'      => ! empty( $entry->request->headers ) ? JSON_PRETTY_PRINT : 0,
		'query_params' => ! empty( $entry->request->query_params ) ? JSON_PRETTY_PRINT : 0,
		'body_params'  => ! empty( $entry->request->body_params ) ? JSON_PRETTY_PRINT : 0,
		),
	'response' => array(
		'headers'      => ! empty( $entry->response->headers ) ? JSON_PRETTY_PRINT : 0,
		),
	);

$json_display_options = apply_filters( 'wp-rest-api-log-json-display-options', $json_display_options, $entry );

$classes = apply_filters( 'wp-rest-api-log-entry-display-classes', array( 'wrap', 'wp-rest-api-log-entry' ), $entry );

$download_urls = WP_REST_API_Log_Controller::get_download_urls( $entry );

?>
<div class="<?php echo implode( ' ', array_map( 'esc_attr',  $classes ) ); ?>" id="wp-rest-api-log-entry">

	<h1><?php esc_html_e( 'Route:', 'wp-rest-api-log' ); ?> <?php echo esc_html( $entry->route ); ?></h1>

	<div id="poststuff">

		<?php do_action( 'wp-rest-api-log-display-entry-before', $entry ); ?>

		<div class="postbox request-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Details', 'wp-rest-api-log' ); ?></span></h3>

			<div class="inside">
				<ul>
					<li><?php esc_html_e( 'Date' ); ?>: <?php echo esc_html( $entry->time ); ?></li>
					<li><?php esc_html_e( 'Source', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->source ); ?></li>
					<li><?php esc_html_e( 'Method', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->method ); ?></li>
					<li><?php esc_html_e( 'Status', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->status ); ?></li>
					<li><?php esc_html_e( 'Elapsed Time', 'wp-rest-api-log' ); ?>: <?php echo esc_html( number_format( $entry->milliseconds ) ); ?>ms</li>
					<li><?php esc_html_e( 'Response Length', 'wp-rest-api-log' ); ?>: <?php echo esc_html( number_format( strlen( $entry->response->body ) ) ); ?></li>
					<li><?php esc_html_e( 'User', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->user ); ?></li>
					<li><?php esc_html_e( 'IP Address', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->ip_address ); ?></li>
					<?php if ( ! empty( $entry->http_x_forwarded_for ) ) : ?>
						<li><?php esc_html_e( 'HTTP X Forwarded For', 'wp-rest-api-log' ); ?>: <?php echo esc_html( $entry->http_x_forwarded_for ); ?></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<?php do_action( 'wp-rest-api-log-display-entry-before-request-headers', $entry ); ?>

		<div class="postbox request-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Request Headers', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside">
				<?php do_action( 'wp-rest-api-log-entry-property-links', array(
						'rr' => 'request',
						'property' => 'headers',
						'download_urls' => $download_urls,
						'entry' => $entry,
					)
				); ?>
				<pre><code class="json"><?php echo esc_html( wp_json_encode( $entry->request->headers, $json_display_options['request']['headers'] ) ); ?></code></pre>
			</div>
		</div>

		<?php do_action( 'wp-rest-api-log-display-entry-before-request-querystring', $entry ); ?>

		<div class="postbox querystring-parameters request-query_params">
			<h3 class="hndle"><span><?php esc_html_e( 'Query Parameters', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside">
				<?php do_action( 'wp-rest-api-log-entry-property-links', array(
						'rr' => 'request',
						'property' => 'query_params',
						'download_urls' => $download_urls,
						'entry' => $entry,
					)
				); ?>
				<pre><code class="json"><?php echo esc_html( wp_json_encode( $entry->request->query_params, $json_display_options['request']['query_params'] ) ); ?></code></pre>
			</div>
		</div>

		<?php do_action( 'wp-rest-api-log-display-entry-before-request-body', $entry ); ?>

		<div class="postbox body-parameters request-body_params">
			<h3 class="hndle"><span><?php esc_html_e( 'Body Parameters', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside">
				<?php do_action( 'wp-rest-api-log-entry-property-links', array(
						'rr' => 'request',
						'property' => 'body_params',
						'download_urls' => $download_urls,
						'entry' => $entry,
					)
				); ?>
				<pre><code class="json"><?php
					echo esc_html( wp_json_encode(
						$entry->request->body_params,
						$json_display_options['request']['body_params']
					) );
					?></code></pre>
			</div>
		</div>


		<?php if ( ! empty( $body_object ) || ! empty( $body_content ) ) : ?>
			<?php do_action( 'wp-rest-api-log-display-entry-before-request-body-content', $entry ); ?>

			<div class="postbox body-content-parameters request-body">
				<h3 class="hndle"><span><?php esc_html_e( 'Body Content', 'wp-rest-api-log' ); ?></span></h3>
				<?php if ( ! empty( $body_object ) ) : ?>
					<div class="inside">
						<?php do_action( 'wp-rest-api-log-entry-property-links', array(
								'rr' => 'request',
								'property' => 'body',
								'download_urls' => $download_urls,
								'entry' => $entry,
							)
						); ?>
						<pre><code class="json"><?php echo esc_html( wp_json_encode( $body_object, JSON_PRETTY_PRINT ) ); ?></code></pre>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $body_content ) ) : ?>
					<div class="inside">
						<?php do_action( 'wp-rest-api-log-entry-property-links', array(
								'rr' => 'request',
								'property' => 'body',
								'download_urls' => $download_urls,
								'entry' => $entry,
							)
						); ?>
						<pre><code><?php echo esc_html( $body_content ); ?></code></pre>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'wp-rest-api-log-display-entry-before-response-headers', $entry ); ?>

		<div class="postbox response-headers response-headers">
			<h3 class="hndle"><span><?php esc_html_e( 'Response Headers', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside">
				<?php do_action( 'wp-rest-api-log-entry-property-links', array(
						'rr' => 'response',
						'property' => 'headers',
						'download_urls' => $download_urls,
						'entry' => $entry,
					)
				); ?>
				<pre><code class="json"><?php echo esc_html( wp_json_encode( $entry->response->headers, $json_display_options['response']['headers'] ) ); ?></code></pre>
			</div>
		</div>

		<?php do_action( 'wp-rest-api-log-display-entry-before-response-body', $entry ); ?>

		<div class="postbox response-body">
			<h3 class="hndle"><span><?php esc_html_e( 'Response Body', 'wp-rest-api-log' ); ?></span></h3>
			<div class="inside">
				<?php do_action( 'wp-rest-api-log-entry-property-links', array(
						'rr' => 'response',
						'property' => 'body',
						'download_urls' => $download_urls,
						'entry' => $entry,
					)
				); ?>
				<pre><code><?php echo esc_html( $entry->response->body ); ?></code></pre>
			</div>
		</div>

		<?php do_action( 'wp-rest-api-log-display-entry-after', $entry ); ?>

	</div>

</div>
<?php

