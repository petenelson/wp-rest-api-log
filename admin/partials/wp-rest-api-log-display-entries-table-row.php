<?php
global $wp_rest_api_log_display_entry;
$e = $wp_rest_api_log_display_entry;
?>
<tr class="entry-row entry-row-<?php echo esc_attr( $e->id ); ?>" data-id="<?php echo esc_attr( $e->id ); ?>">
	<td class="time">
		<?php echo esc_html( mysql2date( get_option( 'date_format'), $e->time ) ); ?> <?php echo esc_html( mysql2date( 'h:i:s a', $e->time ) ); ?>
		<img class="ajax-wait collapsed" src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/ajax-wait.svg'; ?>" />
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

			<div class="inside collapsed"><pre></pre></div>

		</div>

		<div class="postbox querystring-parameters">
			<h3 class=""><span>Querystring Parameters</span></h3>

			<div class="inside"><pre></pre></div>

		</div>

		<div class="postbox body-parameters">
			<h3 class=""><span>Body Parameters</span></h3>

			<div class="inside"><pre></pre></div>

		</div>

		<div class="postbox response-body">
			<h3 class=""><span>Response</span></h3>

			<div class="inside"><pre></pre></div>

		</div>

	</td>

</tr>

