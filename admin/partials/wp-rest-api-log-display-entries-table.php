<?php
global $wp_rest_api_log_display_entries;
global $wp_rest_api_log_display_entry;
?><table class="log-entries wp-list-table widefat fixed items">

<thead>
	<tr>
		<th>Date</th>
		<th>Method</th>
		<th>Route</th>
		<th>Status</th>
		<th>Elapsed Time</th>
		<th>Response Length</th>
		<th>IP Address</th>
	</tr>
</thead>

<tbody class="tbody">
	<?php
		if ( ! empty( $wp_rest_api_log_display_entries ) ) {
			foreach ( $wp_rest_api_log_display_entries as $wp_rest_api_log_display_entry ) {
				require plugin_dir_path( __FILE__ ) . 'wp-rest-api-log-display-entries-table-row.php';
			}
		}
	?>
</tbody>

</table>
