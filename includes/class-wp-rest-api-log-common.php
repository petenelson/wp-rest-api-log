<?php
/**
 * Shared helpers used across the plugin.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Common' ) ) {

	/**
	 * Constants and utility helpers shared by the rest of the plugin.
	 */
	class WP_REST_API_Log_Common {

		const PLUGIN_NAME = 'wp-rest-api-log';
		const VERSION     = WP_REST_API_LOG_VERSION;
		const TEXT_DOMAIN = 'wp-rest-api-log';

		/**
		 * Returns the current time in milliseconds.
		 *
		 * @return float
		 */
		public static function current_milliseconds() {
			return self::microtime_to_milliseconds( microtime() );
		}

		/**
		 * Converts a microtime() string into milliseconds.
		 *
		 * @param  string $microtime Value returned by microtime().
		 * @return float
		 */
		public static function microtime_to_milliseconds( $microtime ) {
			list( $usec, $sec ) = explode( ' ', $microtime );
			return ( ( (float) $usec + (float) $sec ) ) * 1000;
		}


		/**
		 * Returns the HTTP methods the plugin will log.
		 *
		 * @return array Filterable via "wp-rest-api-log-valid-methods".
		 */
		public static function valid_methods() {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- self::PLUGIN_NAME is the "wp-rest-api-log" prefix.
			return apply_filters( self::PLUGIN_NAME . '-valid-methods', array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ) );
		}


		/**
		 * Determines whether an HTTP method should be logged.
		 *
		 * @param  string $method HTTP method name.
		 * @return bool Filterable via "wp-rest-api-log-is-method-valid".
		 */
		public static function is_valid_method( $method ) {
			// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict,WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Loose comparison retained to preserve existing behavior; self::PLUGIN_NAME is the "wp-rest-api-log" prefix.
			return apply_filters( self::PLUGIN_NAME . '-is-method-valid', in_array( $method, self::valid_methods() ) );
		}

		/**
		 * Outputs a select dropdown for a taxonomy.
		 *
		 * @param  string $taxonomy The taxonomy name.
		 * @param  array  $args     Additional args.
		 * @return void
		 */
		public static function dropdown_terms( $taxonomy, $args = array() ) {

			if ( ! taxonomy_exists( $taxonomy ) ) {
				return;
			}

			$tax_obj      = get_taxonomy( $taxonomy );
			$get_taxonomy = self::get_string_query_param( $taxonomy );

			$args = wp_parse_args(
				$args,
				array(
					// Selected term slug.
					'selected'   => '',
					'hide_empty' => false,
					'all_items'  => '',
				)
			);

			// Default the selected slug to the query string if nothing was passed.
			$selected_slug = ! empty( $args['selected'] ) ? $args['selected'] : $get_taxonomy;
			$all_items     = ! empty( $args['all_label'] ) ? $args['all_label'] : $tax_obj->labels->all_items;

			$term_query = new \WP_Term_Query(
				array(
					'taxonomy' => $taxonomy,
					'orderby'  => 'count',
					'order'    => 'DESC',
				)
			);

			?>
			<label class="screen-reader-text" for="<?php echo esc_attr( esc_attr( $taxonomy ) ); ?>">
				<?php echo esc_html( $tax_obj->labels->filter_by_item ); ?>
			</label>

			<select name="<?php echo esc_attr( esc_attr( $taxonomy ) ); ?>" id="<?php echo esc_attr( esc_attr( $taxonomy ) ); ?>">

				<option value=""><?php echo esc_html( $all_items ); ?></option>

				<?php foreach ( $term_query->get_terms() as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_slug, $term->slug ); ?>  ><?php echo esc_html( $term->name ); ?> (<?php echo esc_html( number_format( $term->count ) ); ?>)</option>
				<?php endforeach; ?>

			</select>
			<?php
		}

		/**
		 * Callback filter for filter_var_array() to strip HTML tags.
		 *
		 * @return array
		 */
		public static function filter_strip_all_tags() {
			return array(
				'filter'  => FILTER_CALLBACK,
				'options' => '\wp_strip_all_tags',
			);
		}

		/**
		 * Gets a $_GET querystring parameter.
		 *
		 * @param  string $param Query string parameter name.
		 * @return string
		 */
		public static function get_string_query_param( $param ) {

			$get = filter_var_array(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filtering; no state is changed.
				$_GET,
				array(
					$param => self::filter_strip_all_tags(),
				)
			);

			return $get[ $param ];
		}
	}
}
