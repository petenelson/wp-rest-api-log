<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Common' ) ) {

	class WP_REST_API_Log_Common {

		const PLUGIN_NAME      = 'wp-rest-api-log';
		const VERSION          = '2016-12-05-01';
		const TEXT_DOMAIN      = 'wp-rest-api-log';


		public function plugins_loaded() {

		}


		static public function current_milliseconds() {
			return self::microtime_to_milliseconds( microtime() );
		}

		static public function microtime_to_milliseconds( $microtime ) {
			list( $usec, $sec ) = explode( " ", $microtime );
			return ( ( (float)$usec + (float)$sec ) ) * 1000;
		}


		static public function valid_methods() {
			return apply_filters( self::PLUGIN_NAME . '-valid-methods', array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ) );
		}


		static public function is_valid_method( $method ) {
			return apply_filters( self::PLUGIN_NAME . '-is-method-valid', in_array( $method, self::valid_methods() ) );
		}


		static public function taxonomy_dropdown( $label, $all_items_prompt, $taxonomy, $selected_slug ) {
			$terms = get_terms( $taxonomy );

			?>
				<label for="<?php echo esc_attr( $taxonomy ); ?>" class="screen-reader-text"><?php echo esc_html( $label ) ?></label>
				<select name="<?php echo esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $taxonomy ); ?>">
					<option value=""><?php echo esc_html( $all_items_prompt ); ?></option>
					<?php foreach( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $selected_slug ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>

			<?php
		}


	} // end class

}
