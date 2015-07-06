<?php ?>

<div class="wrap wp-rest-api-log-wrap">

	<h2>WP REST API Log</h2>

	<div class="search-form">

		<form method="get" action="<?php echo site_url( rest_get_url_prefix() . '/wp-rest-api-log/entries' ); ?>">

			<div class="search-params">

				<div class="search-param">
					Date:
					<input type="text" name="from" value="" class="from datetimepicker clear-on-reset" />
					to
					<input type="text" name="from" value="" class="to datetimepicker clear-on-reset" />
				</div>

				<div class="search-param">
					Method:
					<select name="method" class="method clear-on-reset">
						<option></option>
						<option>GET</option>
						<option>POST</option>
						<option>PUT</option>
						<option>PATCH</option>
						<option>DELETE</option>
					</select>
				</div>

				<div class="search-param">
					Route: <input type="text" name="route" value="" class="route clear-on-reset clear-on-reset" />
				</div>

				<div class="search-param">
					Param: <input type="text" name="param_name" value="" class="param_name clear-on-reset" /> : <input type="text" name="param_value" value="" class="param_value clear-on-reset" />
				</div>

				<div class="search-param-buttons">
					<input type="submit" value="<?php _e( 'Search '); ?>" class="button-primary" />
					<button type="submit" class="button button-reset"><?php _e( 'Reset' ); ?></button>
					<?php include plugin_dir_path( __FILE__ ) . 'wp-rest-api-log-display-entries-ajax-wait.php'; ?>
				</div>

			</div><!-- .search-params -->

		</form>

	</div>

	<div class="table-wrap">
		<?php require_once plugin_dir_path( __FILE__ ) . 'wp-rest-api-log-display-entries-table.php'; ?>
	</div>


	<p class="no-matches collapsed">
		No matching log entries
	</p>

</div>


