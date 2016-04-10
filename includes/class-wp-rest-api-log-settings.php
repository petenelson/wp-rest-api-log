<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings' ) ) {

	class WP_REST_API_Log_Settings {

		public $settings_page          = 'wp-rest-api-log-settings';
		public $settings_key_general   = 'wp-rest-api-log-settings-general';
		public $settings_key_help      = 'wp-rest-api-log-settings-help';
		private $plugin_settings_tabs  = array();


		public function plugins_loaded() {

			add_action( 'admin_init', array( $this, 'plugin_upgrade' ) );

			// settings
			add_action( 'admin_init', array( $this, 'register_general_settings' ) );
			add_action( 'admin_init', array( $this, 'register_help_tab' ) );

			// admin menus
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			add_action( 'admin_notices', array( $this, 'activation_admin_notice' ) );

			// filters to get plugin settings
			add_filter( WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-is-enabled', array( $this, 'setting_is_enabled' ), 10, 3 );
			add_filter( WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-get', array( $this, 'setting_get' ), 10, 3 );

		}


		public function activation_admin_notice() {
			if ( '1' === get_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-activated' ) ) {
				?>
					<div class="updated">
						<p>
							<?php echo wp_kses_post( sprintf( __( '<strong>WP REST API Log activated!</strong> Please <a href="%s">visit the Settings page</a> to customize the settings.', 'wp-rest-api-log' ), esc_url( admin_url( 'options-general.php?page=' . urlencode( $this->settings_page ) ) ) ) ); ?>
						</p>
					</div>
				<?php
				delete_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-activated' );
			}
		}


		public function create_default_settings() {
			// create default settings
			add_option( $this->settings_key_general, $this->get_default_settings(), '', $autoload = 'no' );
		}


		public function get_default_settings() {
			return array(
				'logging-enabled'   => '1',
			);
		}

		public function update_setting( $key, $setting, $value ) {
			$option = get_option( $key );
			$option[ $setting ] = $value;
			update_option( $key, $option );
		}

		public function plugin_upgrade( ) {

			$current_version = $this->current_plugin_version();

			// create the default settings if this is the first use of the plugin
			if ( empty( $current_version ) ) {
				$this->create_default_settings();
			}

			// future upgrade settings changes will go here

			if ( $current_version !== WP_REST_API_Log_Common::VERSION ) {
				update_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-version', WP_REST_API_Log_Common::VERSION );
			}

		}


		public function current_plugin_version() {
			return get_option( WP_REST_API_Log_Common::PLUGIN_NAME . '-plugin-version' );
		}


		public function register_general_settings() {
			$key = $this->settings_key_general;
			$this->plugin_settings_tabs[$key] = __( 'General' );

			register_setting( $key, $key, array( $this, 'sanitize_general_settings') );

			$section = 'general';

			add_settings_section( $section, '', array( $this, 'section_header' ), $key );

			add_settings_field( 'logging-enabled', __( 'Enabled' ), array( $this, 'settings_yes_no' ), $key, $section,
				array(
					'key' => $key,
					'name' => 'logging-enabled',
					)
				);

		}


		public function sanitize_general_settings( $settings ) {

			return $settings;
		}


		public function register_help_tab() {
			$key = $this->settings_key_help;
			$this->plugin_settings_tabs[$key] =  __( 'Help' );
			register_setting( $key, $key );
			$section = 'help';
			add_settings_section( $section, '', array( $this, 'section_header' ), $key );
		}


		public function setting_is_enabled( $enabled, $key, $setting ) {
			return '1' === $this->setting_get( '0', $key, $setting );
		}


		public function setting_get( $value, $key, $setting ) {

			$args = wp_parse_args( get_option( $key ),
				array(
					$setting => $value,
				)
			);

			return $args[$setting];
		}


		public function settings_input( $args ) {

			extract( wp_parse_args( $args,
				array(
					'name' => '',
					'key' => '',
					'maxlength' => 50,
					'size' => 30,
					'after' => '',
					'type' => 'text',
					'min' => 0,
					'max' => 0,
					'step' => 1,
				)
			) );


			$option = get_option( $key );
			$value  = isset( $option[ $name ] ) ? esc_attr( $option[ $name ] ) : '';

			$name   = esc_attr( $name );
			$key    = esc_attr( $key );
			$type   = esc_attr( $type );

			$min_max_step = '';
			if ( $type === 'number' ) {
				$min          = intval( $args['min'] );
				$max          = intval( $args['max'] );
				$step         = intval( $args['step'] );
				$min_max_step = " step='{$step}' min='{$min}' max='{$max}' ";
			}

			echo "<div><input id='{$name}' name='{$key}[{$name}]'  type='{$type}' value='" . $value . "' size='{$size}' maxlength='{$maxlength}' {$min_max_step} /></div>";

			$this->output_after( $after );

		}


		public function settings_checkbox_list( $args ) {
			extract( wp_parse_args( $args,
				array(
					'name' => '',
					'key' => '',
					'items' => array(),
					'after' => '',
					'legend' => '',
				)
			) );

			$option = get_option( $key );
			$values = isset( $option[ $name ] ) ? $option[ $name ] : '';

			if ( ! is_array( $values ) ) {
				$values = array();
			}

			?>
				<fieldset>
					<legend class="screen-reader-text">
						<?php echo esc_html( $legend ) ?>
					</legend>

					<?php foreach ( $items as $value => $value_dispay ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $key ); ?>[<?php echo esc_attr( $name ) ?>][]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $values) ); ?> />
							<?php echo esc_html( $value_dispay ); ?>
						</label>
						<br/>
					<?php endforeach; ?>
				</fieldset>
			<?php

		}


		public function settings_textarea( $args ) {

			extract( wp_parse_args( $args,
				array(
					'name' => '',
					'key' => '',
					'rows' => 10,
					'cols' => 40,
					'after' => '',
				)
			) );


			$option   = get_option( $key );
			$value    = isset( $option[ $name ] ) ? esc_attr( $option[ $name ] ) : '';
			$name     = esc_attr( $name );
			$key      = esc_attr( $key );
			$rows     = esc_attr( $rows );
			$cols     = esc_attr( $cols );

			echo "<div><textarea id='{$name}' name='{$key}[{$name}]' rows='{$rows}' cols='{$cols}'>" . $value . "</textarea></div>";

			$this->output_after( $after );

		}


		public function settings_yes_no( $args ) {

			extract( wp_parse_args( $args,
				array(
					'name' => '',
					'key' => '',
					'after' => '',
				)
			) );

			$option   = get_option( $key );
			$value    = isset( $option[ $name ] ) ? esc_attr( $option[ $name ] ) : '';
			$name     = esc_attr( $name );
			$key      = esc_attr( $key );

			if ( empty( $value ) ) {
				$value = '0';
			}

			echo '<div>';
			echo "<label><input id='{$name}_1' name='{$key}[{$name}]'  type='radio' value='1' " . ( '1' === $value ? " checked=\"checked\"" : "" ) . "/>" . esc_html__( 'Yes' ) . "</label> ";
			echo "<label><input id='{$name}_0' name='{$key}[{$name}]'  type='radio' value='0' " . ( '0' === $value ? " checked=\"checked\"" : "" ) . "/>" . esc_html__( 'No' ) . "</label> ";
			echo '</div>';

			$this->output_after( $after );

		}


		public function output_after( $after ) {
			if ( ! empty( $after ) ) {
				echo '<div>' . wp_kses_post( $after ) . '</div>';
			}
		}


		public function admin_menu() {
			add_options_page( 'REST API Log' . __( 'Settings' ), __( 'REST API Log', 'wp-rest-api-log' ), 'manage_options', $this->settings_page, array( $this, 'options_page' ), 30 );
		}


		public function options_page() {

			$tab = $this->current_tab(); ?>
			<div class="wrap">
				<?php $this->plugin_options_tabs(); ?>
				<form method="post" action="options.php" class="options-form">
					<?php settings_fields( $tab ); ?>
					<?php do_settings_sections( $tab ); ?>
					<?php
						if ( $this->settings_key_help !== $tab ) {
							submit_button( __( 'Save Changes' ), 'primary', 'submit', true );
						}
					?>
				</form>
			</div>
			<?php

			$settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_STRING );
			if ( ! empty( $settings_updated ) ) {
				do_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-flush-sizes-transient' );
			}

		}


		public function current_tab() {
			$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
			return empty( $current_tab ) ? $this->settings_key_general : $current_tab;
		}


		public function plugin_options_tabs() {
			$current_tab = $this->current_tab();
			echo '<h2>' . __( 'Settings' ) . ' &rsaquo; WP REST API Log</h2><h2 class="nav-tab-wrapper">';
			foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
				$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
				echo '<a class="nav-tab ' . $active . '" href="?page=' . urlencode( $this->settings_page ) . '&tab=' . urlencode( $tab_key ) . '">' . esc_html( $tab_caption ) . '</a>';
			}
			echo '</h2>';
		}


		public function section_header( $args ) {

			switch ( $args['id'] ) {
				case 'help';
					include_once WP_REST_API_LOG_ROOT . 'admin/partials/admin-help.php';
					break;
			}

		}


	} // end class

}