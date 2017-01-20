<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings_Routes' ) ) {

	class WP_REST_API_Log_Settings_Routes extends WP_REST_API_Log_Settings_Base {

		static $settings_key  = 'wp-rest-api-log-settings-routes';


		static public function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_general_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab') );
		}


		static public function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Routes', 'wp-rest-api-log' );
			return $tabs;
		}


		static public function get_default_settings() {
			return array(
				'ignore-core-oembed'   => '1',
			);
		}


		static public function register_general_settings() {
			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings') );

			$section = 'routes';

			add_settings_section( $section, '', null, $key );

			add_settings_field(
				'ignore-core-oembed',
				__( 'Ignore core oEmbed', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_yes_no' ),
				$key,
				$section,
				array(
					'key' => $key,
					'name' => 'ignore-core-oembed',
					'after' => '<p class="description">Built-in /oembed/1.0/embed route</p>'
					)
				);

			add_settings_field(
				'route-log-matching-mode',
				__( 'Route Logging Mode', 'wp-rest-api-log' ),
				array( __CLASS__, 'log_matching_mode_dropdown' ),
				$key,
				$section,
				array(
					'key' => $key,
					'name' => 'route-log-matching-mode',
					)
				);

			add_settings_field(
				'route-filters',
				__( 'Route Filters', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_textarea' ),
				$key,
				$section,
				array(
					'key' => $key,
					'name' => 'route-filters',
					'after' => '
						<p class="description">One route per line, examples</p>
						<ul>
							<li>Exact Match: /wp/v2/posts</li>
							<li>Wildcard Match: /wp/v2/*</li>
							<li>Regex: ^/wp/v2/.*$</li>
						</ul>
						<p class="description">Regex matches must start with ^</p>
					',
					)
				);
		}

		/**
		 * Callback for outputting the log matching mode.
		 *
		 * @param  array $args
		 * @return void
		 */
		static public function log_matching_mode_dropdown( $args ) {

			$args = wp_parse_args( $args, array(
				'key'  => '',
				'name' => '',
				)
			);

			$option = get_option( $args['key'] );
			$mode = isset( $option[ $args['name'] ] ) ? $option[ $args['name'] ] : '';

			// Get the list of filter modes.
			$filter_modes = WP_REST_API_Log_Filters::filter_modes();

			?>
				<select name="<?php echo esc_attr( "{$args['key']}[{$args['name']}]"); ?>">
					<?php foreach( $filter_modes as $value => $name ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $mode ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php

		}


		static public function sanitize_settings( $settings ) {

			return $settings;
		}


	}

}

