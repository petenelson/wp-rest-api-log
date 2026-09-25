<?php
/**
 * Class WP_REST_API_Log_Test_Settings_Base
 *
 * @package wp-rest-api-log
 */

/**
 * Tests for reading, writing and rendering settings.
 */
class WP_REST_API_Log_Test_Settings_Base extends WP_UnitTestCase {

	/**
	 * Option used by the rendering tests.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp-rest-api-log-settings-general';

	/**
	 * Captures the output of a callable.
	 *
	 * @param  callable $callback Callback that prints output.
	 * @param  array    $args     Arguments for the callback.
	 * @return string
	 */
	protected function capture( $callback, $args ) {
		ob_start();
		call_user_func( $callback, $args );
		return ob_get_clean();
	}

	/**
	 * Tests the known settings groups.
	 *
	 * @return void
	 */
	public function test_settings_keys() {

		$this->assertSame( array( 'general' ), array_keys( WP_REST_API_Log_Settings_Base::settings_keys() ) );
		$this->assertTrue( WP_REST_API_Log_Settings_Base::settings_key_is_valid( 'general' ) );
		$this->assertFalse( WP_REST_API_Log_Settings_Base::settings_key_is_valid( 'nope' ) );
		$this->assertSame( 'wp-rest-api-log-settings-general', WP_REST_API_Log_Settings_Base::options_key( 'general' ) );
	}

	/**
	 * Tests turning a boolean setting on and off.
	 *
	 * @return void
	 */
	public function test_change_enabled_setting() {

		delete_option( self::OPTION_KEY );

		$this->assertTrue( WP_REST_API_Log_Settings_Base::change_enabled_setting( 'general', 'logging-enabled', true ) );
		$this->assertSame( array( 'logging-enabled' => '1' ), get_option( self::OPTION_KEY ) );
		$this->assertTrue( WP_REST_API_Log_Settings_Base::setting_is_enabled( 'general', 'logging-enabled' ) );

		$this->assertTrue( WP_REST_API_Log_Settings_Base::change_enabled_setting( 'general', 'logging-enabled', false ) );
		$this->assertSame( array( 'logging-enabled' => '0' ), get_option( self::OPTION_KEY ) );
		$this->assertFalse( WP_REST_API_Log_Settings_Base::setting_is_enabled( 'general', 'logging-enabled' ) );

		$this->assertFalse( WP_REST_API_Log_Settings_Base::change_enabled_setting( 'nope', 'logging-enabled', true ) );
		$this->assertFalse( get_option( 'wp-rest-api-log-settings-nope' ) );
	}

	/**
	 * Tests changing a setting, with and without a sanitize callback.
	 *
	 * @return void
	 */
	public function test_change_setting() {

		delete_option( self::OPTION_KEY );

		$this->assertTrue( WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '30' ) );
		$this->assertSame( '30', WP_REST_API_Log_Settings_Base::setting_get( 'general', 'purge-days' ) );

		WP_REST_API_Log_Settings_Base::change_setting( 'general', 'purge-days', '0', array( 'WP_REST_API_Log_Settings_General', 'sanitize_settings' ) );
		$this->assertSame( '', WP_REST_API_Log_Settings_Base::setting_get( 'general', 'purge-days' ) );

		$this->assertFalse( WP_REST_API_Log_Settings_Base::change_setting( 'nope', 'purge-days', '1' ) );
	}

	/**
	 * Tests reading settings through the plugin's filters.
	 *
	 * @return void
	 */
	public function test_setting_filters() {

		update_option(
			self::OPTION_KEY,
			array(
				'logging-enabled' => '1',
				'purge-days'      => '14',
			)
		);

		$this->assertSame( '14', apply_filters( 'wp-rest-api-log-setting-get', 'general', 'purge-days' ) );
		$this->assertSame( 'fallback', apply_filters( 'wp-rest-api-log-setting-get', 'general', 'missing', 'fallback' ) );
		$this->assertTrue( apply_filters( 'wp-rest-api-log-setting-is-enabled', false, 'general', 'logging-enabled' ) );
		$this->assertFalse( apply_filters( 'wp-rest-api-log-setting-is-enabled', true, 'general', 'missing' ) );
		$this->assertFalse( WP_REST_API_Log_Settings_Base::filter_setting_is_enabled( true, 'general', 'missing' ) );
	}

	/**
	 * Tests rendering a text input.
	 *
	 * @return void
	 */
	public function test_settings_input() {

		update_option( self::OPTION_KEY, array( 'purge-days' => '7' ) );

		$html = $this->capture(
			array( 'WP_REST_API_Log_Settings_Base', 'settings_input' ),
			array(
				'key'       => self::OPTION_KEY,
				'name'      => 'purge-days',
				'size'      => 3,
				'maxlength' => 3,
				'after'     => '<p class="description">After<script>alert(1)</script></p>',
			)
		);

		$this->assertStringContainsString( "id='purge-days'", $html );
		$this->assertStringContainsString( "name='wp-rest-api-log-settings-general[purge-days]'", $html );
		$this->assertStringContainsString( "type='text'", $html );
		$this->assertStringContainsString( "value='7'", $html );
		$this->assertStringContainsString( "size='3'", $html );
		$this->assertStringContainsString( "maxlength='3'", $html );
		$this->assertStringNotContainsString( 'step=', $html );

		// The markup after the field is passed through wp_kses_post().
		$this->assertStringContainsString( '<p class="description">After', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * Tests rendering a number input.
	 *
	 * @return void
	 */
	public function test_settings_input_number() {

		delete_option( self::OPTION_KEY );

		$html = $this->capture(
			array( 'WP_REST_API_Log_Settings_Base', 'settings_input' ),
			array(
				'key'  => self::OPTION_KEY,
				'name' => 'purge-days',
				'type' => 'number',
				'min'  => '1',
				'max'  => '365',
				'step' => '2',
			)
		);

		$this->assertStringContainsString( "type='number'", $html );
		$this->assertStringContainsString( "value=''", $html );
		$this->assertStringContainsString( "step='2' min='1' max='365'", $html );
	}

	/**
	 * Tests rendering a list of checkboxes.
	 *
	 * @return void
	 */
	public function test_settings_check_radio_list_checkboxes() {

		update_option( self::OPTION_KEY, array( 'colors' => array( 'red', 'blue' ) ) );

		$html = $this->capture(
			array( 'WP_REST_API_Log_Settings_Base', 'settings_check_radio_list' ),
			array(
				'key'    => self::OPTION_KEY,
				'name'   => 'colors',
				'legend' => 'Pick colors',
				'items'  => array(
					'red'   => 'Red',
					'green' => 'Green',
					'blue'  => 'Blue',
				),
			)
		);

		$this->assertStringContainsString( 'Pick colors', $html );
		$this->assertSame( 3, substr_count( $html, 'type="checkbox"' ) );
		$this->assertSame( 3, substr_count( $html, 'name="wp-rest-api-log-settings-general[colors][]"' ) );
		$this->assertSame( 2, substr_count( $html, "checked='checked'" ) );
		$this->assertMatchesRegularExpression( '/id="wp-rest-api-log-settings-general_colors_red"[^>]*checked=/', $html );
		$this->assertDoesNotMatchRegularExpression( '/id="wp-rest-api-log-settings-general_colors_green"[^>]*checked=/', $html );
	}

	/**
	 * Tests rendering radio buttons with a saved value and with a default.
	 *
	 * @return void
	 */
	public function test_settings_check_radio_list_radios() {

		$args = array(
			'key'     => self::OPTION_KEY,
			'name'    => 'ip-address-display',
			'type'    => 'radio',
			'after'   => '<p>After</p>',
			'items'   => array(
				'ip_address'           => 'IP Address',
				'http_x_forwarded_for' => 'HTTP X Forwarded For',
			),
			'default' => array( 'ip_address' ),
		);

		// Nothing saved, the default is selected.
		delete_option( self::OPTION_KEY );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Base', 'settings_check_radio_list' ), $args );

		$this->assertSame( 2, substr_count( $html, 'type="radio"' ) );
		$this->assertSame( 2, substr_count( $html, 'name="wp-rest-api-log-settings-general[ip-address-display]"' ) );
		$this->assertMatchesRegularExpression( '/value="ip_address"\s+checked=/', $html );
		$this->assertStringContainsString( '<p>After</p>', $html );

		// A saved scalar value is selected.
		update_option( self::OPTION_KEY, array( 'ip-address-display' => 'http_x_forwarded_for' ) );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Base', 'settings_check_radio_list' ), $args );

		$this->assertMatchesRegularExpression( '/value="http_x_forwarded_for"\s+checked=/', $html );
		$this->assertSame( 1, substr_count( $html, "checked='checked'" ) );
	}

	/**
	 * Tests rendering a textarea.
	 *
	 * @return void
	 */
	public function test_settings_textarea() {

		update_option( self::OPTION_KEY, array( 'notes' => "one\ntwo & <three>" ) );

		$html = $this->capture(
			array( 'WP_REST_API_Log_Settings_Base', 'settings_textarea' ),
			array(
				'key'   => self::OPTION_KEY,
				'name'  => 'notes',
				'rows'  => 5,
				'cols'  => 20,
				'after' => '<p>After</p>',
			)
		);

		$this->assertStringContainsString( '<textarea id="notes" name="wp-rest-api-log-settings-general[notes]" rows="5" cols="20">', $html );
		$this->assertStringContainsString( "one\ntwo &amp; &lt;three&gt;</textarea>", $html );
		$this->assertStringContainsString( '<p>After</p>', $html );
	}

	/**
	 * Tests rendering the yes/no radio buttons.
	 *
	 * @return void
	 */
	public function test_settings_yes_no() {

		$args = array(
			'key'  => self::OPTION_KEY,
			'name' => 'logging-enabled',
		);

		update_option( self::OPTION_KEY, array( 'logging-enabled' => '1' ) );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Base', 'settings_yes_no' ), $args );

		$this->assertStringContainsString( "id='logging-enabled_1' name='wp-rest-api-log-settings-general[logging-enabled]'  type='radio' value='1'  checked=\"checked\"/>", $html );
		$this->assertStringContainsString( "id='logging-enabled_0' name='wp-rest-api-log-settings-general[logging-enabled]'  type='radio' value='0' />", $html );

		// A missing value is treated as "No".
		delete_option( self::OPTION_KEY );
		$html = $this->capture( array( 'WP_REST_API_Log_Settings_Base', 'settings_yes_no' ), $args );

		$this->assertStringContainsString( "value='1' />", $html );
		$this->assertStringContainsString( "value='0'  checked=\"checked\"/>", $html );
	}

	/**
	 * Tests the markup printed after a field.
	 *
	 * @return void
	 */
	public function test_output_after() {

		ob_start();
		WP_REST_API_Log_Settings_Base::output_after( '' );
		$this->assertSame( '', ob_get_clean() );

		ob_start();
		WP_REST_API_Log_Settings_Base::output_after( '<em>ok</em><script>bad()</script>' );
		$this->assertSame( '<em>ok</em>bad()', ob_get_clean() );
	}
}
