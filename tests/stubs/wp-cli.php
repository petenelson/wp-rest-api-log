<?php
/**
 * Minimal stand-ins for the WP-CLI classes used by the plugin's commands,
 * so the commands can be tested without WP-CLI.
 *
 * WP_CLI::error() stops the command by throwing an exception instead of
 * exiting, and every message is recorded so tests can assert on it.
 *
 * @package wp-rest-api-log
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test doubles are kept together in one file.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- The names must match WP-CLI's.

if ( ! class_exists( 'WP_CLI' ) ) {

	/**
	 * Records the messages that WP-CLI would print.
	 */
	class WP_CLI {

		/**
		 * Messages, each a list of type and text.
		 *
		 * @var array
		 */
		public static $messages = array();

		/**
		 * Registered commands, keyed by name.
		 *
		 * @var array
		 */
		public static $commands = array();

		/**
		 * Registers a command.
		 *
		 * @param string $name       Command name.
		 * @param string $class_name Class name.
		 * @return void
		 */
		public static function add_command( $name, $class_name ) {
			self::$commands[ $name ] = $class_name;
		}

		/**
		 * Records a success message.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public static function success( $message ) {
			self::$messages[] = array( 'success', $message );
		}

		/**
		 * Records an error message and stops the command.
		 *
		 * @param string $message Message.
		 * @throws WP_REST_API_Log_Test_WP_CLI_Error Always.
		 * @return void
		 */
		public static function error( $message ) {
			self::$messages[] = array( 'error', $message );
			throw new WP_REST_API_Log_Test_WP_CLI_Error( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test exception, never displayed.
		}

		/**
		 * Records a line of output.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public static function line( $message = '' ) {
			self::$messages[] = array( 'line', $message );
		}
	}
}

if ( ! class_exists( 'WP_CLI_Command' ) ) {

	/**
	 * Base class for WP-CLI commands.
	 */
	class WP_CLI_Command {}
}

/**
 * Thrown by the WP_CLI::error() stand-in.
 */
class WP_REST_API_Log_Test_WP_CLI_Error extends Exception {}

/**
 * Counts the ticks of a progress bar.
 */
class WP_REST_API_Log_Test_Progress_Bar {

	/**
	 * Progress bar message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Number of ticks.
	 *
	 * @var int
	 */
	public $ticks = 0;

	/**
	 * Whether finish() was called.
	 *
	 * @var bool
	 */
	public $finished = false;

	/**
	 * The progress bars that were created.
	 *
	 * @var array
	 */
	public static $bars = array();

	/**
	 * Creates a progress bar.
	 *
	 * @param string $message Message.
	 */
	public function __construct( $message ) {
		$this->message = $message;
		self::$bars[]  = $this;
	}

	/**
	 * Displays the progress bar.
	 *
	 * @return void
	 */
	public function display() {}

	/**
	 * Advances the progress bar.
	 *
	 * @return void
	 */
	public function tick() {
		++$this->ticks;
	}

	/**
	 * Finishes the progress bar.
	 *
	 * @return void
	 */
	public function finish() {
		$this->finished = true;
	}
}
