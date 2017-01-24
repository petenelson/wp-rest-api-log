<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings_Routes' ) ) {

	class WP_REST_API_Log_Settings_Routes extends WP_REST_API_Log_Settings_Base {

		static $settings_key  = 'wp-rest-api-log-settings-routes';

		/**
		 * Hooks up WorPress actions and filters.
		 *
		 * @return void
		 */
		static public function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_routes_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab') );
		}

		/**
		 * Adds a Routes tab.
		 *
		 * @param array $tabs List of tabs.
		 * @return array
		 */
		static public function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Routes', 'wp-rest-api-log' );
			return $tabs;
		}

		/**
		 * Gets the default Routes settings.
		 *
		 * @return array
		 */
		static public function get_default_settings() {
			return array(
				'ignore-core-oembed'         => '1',
				'route-log-matching-mode'    => '',
				'route-filters'              => '',
			);
		}

		/**
		 * Registers settings sections and fields for the Routes tab.
		 *
		 * @return void
		 */
		static public function register_routes_settings() {
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
					'after' => '<p class="description">' . __( 'Built-in /oembed/1.0/embed route', 'wp-rest-api-log' ) . '</p>',
					)
				);

			add_settings_field(
				'route-log-matching-mode',
				__( 'Route Logging Mode', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_check_radio_list' ),
				$key,
				$section,
				array(
					'key' => $key,
					'name' => 'route-log-matching-mode',
					'type' => 'radio',
					'items' => WP_REST_API_Log_Filters::filter_modes(),
					'default' => array( '' ),
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
						<p class="description">' . __( 'One route per line, examples', 'wp-rest-api-log' )  . '</p>
						<ul>
							<li>' . __( 'Exact Match', 'wp-rest-api-log' ) . ': /wp/v2/posts</li>
							<li>' . __( 'Wildcard Match', 'wp-rest-api-log' ) . ': /wp/v2/*</li>
							<li>' . __( 'Regex', 'wp-rest-api-log' ) . ': ^\/wp\/v2\/.*$</li>
						</ul>
						<p class="description">' . __( 'Regex matches must start with ^', 'wp-rest-api-log' ) . '</p>',
					)
				);
		}

		/**
		 * Sanitzes the route settings.
		 *
		 * @param  array $settings List of settings.
		 * @return array
		 */
		static public function sanitize_settings( $settings ) {

			// Sanitize string fields.
			$string_fields = array(
				'ignore-core-oembed',
				'route-log-matching-mode',
				);

			foreach( $string_fields as $field ) {
				if ( isset( $settings[ $field ] ) ) {
					$settings[ $field ] = filter_var( $settings[ $field ], FILTER_SANITIZE_STRING );
				}
			}

			return $settings;
		}


	}

}

