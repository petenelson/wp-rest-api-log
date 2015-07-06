<?php
global $wp_rest_api_log_display_entry;
$e = $wp_rest_api_log_display_entry;
?>
<tr class="entry-row entry-row-<?php echo esc_attr( $e->id ); ?>" data-id="<?php echo esc_attr( $e->id ); ?>">
	<td class="time">
		<?php echo esc_html( mysql2date( get_option( 'date_format'), $e->time ) ); ?> <?php echo esc_html( mysql2date( 'h:i:s a', $e->time ) ); ?>
		<?php include plugin_dir_path( __FILE__ ) . 'wp-rest-api-log-display-entries-ajax-wait.php'; ?>
	</td>
	<td class-"method"><?php echo esc_html( $e->method ); ?></td>
	<td class-"route"><?php echo esc_html( $e->route ); ?></td>
	<td class-"elasped-time"><?php echo esc_html( number_format( $e->milliseconds ) ); ?></td>
	<td class-"response-body-length"><?php echo esc_html( number_format( $e->response_body_length ) ); ?></td>
</tr>
<tr class="entry-details entry-details-<?php echo esc_attr( $e->id ); ?> collapsed">

	<td colspan="5">

		<div class="postbox request-headers">
			<h3 class=""><span>Request Headers</span></h3>

			<div class="inside collapsed"><pre><code></code></pre></div>

		</div>

		<div class="postbox querystring-parameters">
			<h3 class=""><span>Query Parameters</span></h3>

			<div class="inside"><pre><code></code></pre></div>

		</div>

		<div class="postbox body-parameters">
			<h3 class=""><span>Body Parameters</span></h3>

			<div class="inside"><pre><code></code></pre></div>

		</div>

		<div class="postbox response-headers">
			<h3 class=""><span>Response Headers</span></h3>

			<div class="inside collapsed"><pre><code></code></pre></div>

		</div>

		<div class="postbox response-body">
			<h3 class=""><span>Response</span></h3>

			<div class="inside"><pre><code></code></pre></div>

		</div>

	</td>

</tr>

