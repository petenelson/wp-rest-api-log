<?php
/**
 * Shared helpers for reading, writing and rendering plugin settings.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_Base' ) ) {

	/**
	 * Base class providing settings accessors and Settings API field renderers.
	 */
	class WP_REST_API_Log_Settings_Base {

		/**
		 * Slug of the plugin's settings page, also used as the option prefix.
		 *
		 * @var string
		 */
		public static $settings_page = 'wp-rest-api-log-settings';

		/**
		 * Turns a boolean setting on or off.
		 *
		 * @param  string $key     Settings group key, for example "general".
		 * @param  string $setting Name of the setting within the group.
		 * @param  bool   $enabled Whether the setting should be enabled.
		 * @return bool True on success, false if the key is invalid.
		 */
		public static function change_enabled_setting( $key, $setting, $enabled ) {
			if ( ! self::settings_key_is_valid( $key ) ) {
				return false;
			}

			$options_key = self::options_key( $key );
			$option      = get_option( $options_key );
			if ( false === $option ) {
				$option = array();
			}

			$option[ $setting ] = $enabled ? '1' : '0';

			return update_option( $options_key, $option );
		}

		/**
		 * Updates a single setting within a settings group.
		 *
		 * @param  string        $key               Settings group key.
		 * @param  string        $setting           Name of the setting within the group.
		 * @param  mixed         $value             New value for the setting.
		 * @param  callable|null $sanitize_callback Optional callback applied to the whole group.
		 * @return bool True on success, false if the key is invalid.
		 */
		public static function change_setting( $key, $setting, $value, $sanitize_callback = null ) {
			if ( ! self::settings_key_is_valid( $key ) ) {
				return false;
			}

			$options_key = self::options_key( $key );
			$option      = get_option( $options_key );
			if ( false === $option ) {
				$option = array();
			}

			$option[ $setting ] = $value;

			if ( ! empty( $sanitize_callback ) ) {
				$option = call_user_func( $sanitize_callback, $option );
			}

			return update_option( $options_key, $option );
		}


		/**
		 * Determines whether a settings group key is one the plugin knows about.
		 *
		 * @param  string $key Settings group key.
		 * @return bool
		 */
		public static function settings_key_is_valid( $key ) {
			// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Loose comparison retained to preserve existing behavior.
			return in_array( $key, array_keys( self::settings_keys() ) );
		}


		/**
		 * Returns the available settings groups and their labels.
		 *
		 * @return array Labels keyed by settings group key.
		 */
		public static function settings_keys() {
			return array(
				'general' => __( 'General', 'wp-rest-api-log' ),
			);
		}


		/**
		 * Determines whether a boolean setting is turned on.
		 *
		 * @param  string $key     Settings group key.
		 * @param  string $setting Name of the setting within the group.
		 * @return bool
		 */
		public static function setting_is_enabled( $key, $setting ) {
			return '1' === self::setting_get( $key, $setting, '0' );
		}

		/**
		 * Filter callback for "wp-rest-api-log-setting-is-enabled".
		 *
		 * @param  bool   $enabled Incoming value, ignored.
		 * @param  string $key     Settings group key.
		 * @param  string $setting Name of the setting within the group.
		 * @return bool
		 */
		public static function filter_setting_is_enabled( $enabled, $key, $setting ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature is fixed by the filter.
			return self::setting_is_enabled( $key, $setting );
		}

		/**
		 * Reads a single setting, falling back to a default.
		 *
		 * @param  string $key     Settings group key.
		 * @param  string $setting Name of the setting within the group.
		 * @param  mixed  $value   Default returned when the setting is not set.
		 * @return mixed
		 */
		public static function setting_get( $key, $setting, $value = '' ) {

			$args = wp_parse_args(
				get_option( self::options_key( $key ) ),
				array(
					$setting => $value,
				)
			);

			return $args[ $setting ];
		}


		/**
		 * Builds the option name that stores a settings group.
		 *
		 * @param  string $key Settings group key.
		 * @return string
		 */
		public static function options_key( $key ) {
			return self::$settings_page . "-{$key}";
		}

		/**
		 * Renders a text or number input for the Settings API.
		 *
		 * @param  array $args {
		 *     Field arguments.
		 *
		 *     @type string $name      Setting name within the group.
		 *     @type string $key       Option name holding the settings group.
		 *     @type int    $maxlength Maximum input length.
		 *     @type int    $size      Rendered input size.
		 *     @type string $after     Markup appended after the field.
		 *     @type string $type      Input type, "text" or "number".
		 *     @type int    $min       Minimum value for number inputs.
		 *     @type int    $max       Maximum value for number inputs.
		 *     @type int    $step      Step value for number inputs.
		 * }
		 * @return void
		 */
		public static function settings_input( $args ) {

			$args = wp_parse_args(
				$args,
				array(
					'name'      => '',
					'key'       => '',
					'maxlength' => 50,
					'size'      => 30,
					'after'     => '',
					'type'      => 'text',
					'min'       => 0,
					'max'       => 0,
					'step'      => 1,
				)
			);

			$name      = $args['name'];
			$key       = $args['key'];
			$maxlength = $args['maxlength'];
			$size      = $args['size'];
			$after     = $args['after'];
			$type      = $args['type'];

			$option = get_option( $key );
			$value  = isset( $option[ $name ] ) ? $option[ $name ] : '';

			$min_max_step = '';
			if ( 'number' === $type ) {
				$min          = intval( $args['min'] );
				$max          = intval( $args['max'] );
				$step         = intval( $args['step'] );
				$min_max_step = " step='{$step}' min='{$min}' max='{$max}' ";
			}

			printf(
				"<div><input id='%1\$s' name='%2\$s'  type='%3\$s' value='%4\$s' size='%5\$s' maxlength='%6\$s' %7\$s /></div>",
				esc_attr( $name ),
				esc_attr( "{$key}[{$name}]" ),
				esc_attr( $type ),
				esc_attr( $value ),
				esc_attr( $size ),
				esc_attr( $maxlength ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from intval() values only.
				$min_max_step
			);

			self::output_after( $after );
		}


		/**
		 * Renders a list of checkboxes or radio buttons for the Settings API.
		 *
		 * @param  array $args {
		 *     Field arguments.
		 *
		 *     @type string $name    Setting name within the group.
		 *     @type string $type    Input type, "checkbox" or "radio".
		 *     @type string $key     Option name holding the settings group.
		 *     @type array  $items   Labels keyed by stored value.
		 *     @type string $after   Markup appended after the field.
		 *     @type string $legend  Screen reader legend for the fieldset.
		 *     @type array  $default Values selected when nothing is stored.
		 * }
		 * @return void
		 */
		public static function settings_check_radio_list( $args ) {

			$args = wp_parse_args(
				$args,
				array(
					'name'    => '',
					'type'    => 'checkbox',
					'key'     => '',
					'items'   => array(),
					'after'   => '',
					'legend'  => '',
					'default' => array(),
				)
			);

			$name    = $args['name'];
			$type    = $args['type'];
			$key     = $args['key'];
			$items   = $args['items'];
			$after   = $args['after'];
			$legend  = $args['legend'];
			$default = $args['default'];

			$option = get_option( $key );
			$values = isset( $option[ $name ] ) ? $option[ $name ] : '';
			if ( ! is_array( $values ) && ! empty( $values ) ) {
				$values = array( $values );
			}

			if ( empty( $values ) && ! empty( $default ) ) {
				$values = $default;
			}

			$input_name = "{$key}[{$name}]";
			if ( 'checkbox' === $type ) {
				$input_name .= '[]';
			}

			?>
				<fieldset>
					<legend class="screen-reader-text">
						<?php echo esc_html( $legend ); ?>
					</legend>

					<?php
					foreach ( $items as $value => $value_dispay ) :
						$id = $key . '_' . $name . '_' . sanitize_key( $value );
						?>
						<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $values ) ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Loose comparison retained to preserve existing behavior. ?> />
						<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $value_dispay ); ?></label>
						<br/>
					<?php endforeach; ?>
				</fieldset>
			<?php

			self::output_after( $after );
		}


		/**
		 * Renders a textarea for the Settings API.
		 *
		 * @param  array $args {
		 *     Field arguments.
		 *
		 *     @type string $name  Setting name within the group.
		 *     @type string $key   Option name holding the settings group.
		 *     @type int    $rows  Number of rows.
		 *     @type int    $cols  Number of columns.
		 *     @type string $after Markup appended after the field.
		 * }
		 * @return void
		 */
		public static function settings_textarea( $args ) {

			$args = wp_parse_args(
				$args,
				array(
					'name'  => '',
					'key'   => '',
					'rows'  => 10,
					'cols'  => 40,
					'after' => '',
				)
			);

			$name  = $args['name'];
			$key   = $args['key'];
			$rows  = $args['rows'];
			$cols  = $args['cols'];
			$after = $args['after'];

			$option = get_option( $key );
			$value  = isset( $option[ $name ] ) ? $option[ $name ] : '';

			printf(
				'<div><textarea id="%1$s" name="%2$s" rows="%3$s" cols="%4$s">%5$s</textarea></div>',
				esc_attr( $name ),
				esc_attr( "{$key}[{$name}]" ),
				esc_attr( $rows ),
				esc_attr( $cols ),
				esc_attr( $value )
			);

			self::output_after( $after );
		}


		/**
		 * Renders a yes/no radio pair for the Settings API.
		 *
		 * @param  array $args {
		 *     Field arguments.
		 *
		 *     @type string $name  Setting name within the group.
		 *     @type string $key   Option name holding the settings group.
		 *     @type string $after Markup appended after the field.
		 * }
		 * @return void
		 */
		public static function settings_yes_no( $args ) {

			$args = wp_parse_args(
				$args,
				array(
					'name'  => '',
					'key'   => '',
					'after' => '',
				)
			);

			$name  = $args['name'];
			$key   = $args['key'];
			$after = $args['after'];

			$option = get_option( $key );
			$value  = isset( $option[ $name ] ) ? esc_attr( $option[ $name ] ) : '';

			if ( empty( $value ) ) {
				$value = '0';
			}

			$checked_yes = '1' === $value ? ' checked="checked"' : '';
			$checked_no  = '0' === $value ? ' checked="checked"' : '';

			echo '<div>';
			printf(
				"<label><input id='%1\$s' name='%2\$s'  type='radio' value='1' %3\$s/>%4\$s</label> ",
				esc_attr( $name . '_1' ),
				esc_attr( "{$key}[{$name}]" ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup with no dynamic content.
				$checked_yes,
				// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Intentionally reuses WordPress core's translation of this string.
				esc_html__( 'Yes' )
			);
			printf(
				"<label><input id='%1\$s' name='%2\$s'  type='radio' value='0' %3\$s/>%4\$s</label> ",
				esc_attr( $name . '_0' ),
				esc_attr( "{$key}[{$name}]" ),
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup with no dynamic content.
				$checked_no,
				// phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Intentionally reuses WordPress core's translation of this string.
				esc_html__( 'No' )
			);
			echo '</div>';

			self::output_after( $after );
		}


		/**
		 * Outputs the markup appended after a settings field.
		 *
		 * @param  string $after Markup to output.
		 * @return void
		 */
		public static function output_after( $after ) {
			if ( ! empty( $after ) ) {
				echo wp_kses_post( $after );
			}
		}
	}

}
