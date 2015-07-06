<?php
global $wp_rest_api_log_display_entry;
$e = $wp_rest_api_log_display_entry;
?>
<tr>
	<td class="time"><?php echo esc_html( mysql2date( get_option( 'date_format'), $e->time ) ); ?> <?php echo esc_html( mysql2date( 'h:i:s a', $e->time ) ); ?></td>
	<td class-"route"><?php echo esc_html( $e->route ); ?></td>
	<td class-"elasped-time"><?php echo esc_html( number_format( $e->milliseconds ) ); ?></td>
	<td class-"response-body-length"><?php echo esc_html( number_format( $e->response_body_length ) ); ?></td>
</tr>
