<?php
/**
 * Javascript hooks for Front End Form Extension for ACF
 * @author Tafhim Ul Islam (The Portland Company)
 * @package TPC Front End Form Extension for ACF
 */

class ACFFrontendFormAdminRawScripts {
	/**
	 * Instance object
	 */
	private static $instance;

	private static $head_raw_scripts;
	private static $footer_raw_scripts;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$head_raw_scripts = '';
		self::$footer_raw_scripts = '';

		add_action('admin_head', array($this, 'print_to_head'));
		add_action('admin_footer', array($this, 'print_to_footer'));
	}

	/**
	 * Creates an instance of this class if needed
	 * @return Object Instance of this class
	 */
	public static function instance( ) {
		if( !self::$instance ) {
			self::$instance = new self( );
		}

		return self::$instance;
	}

	/**
	 * Adder for script files that apply for front end head
	 */
	public static function add_to_head( $raw_script_string ) {
		if ( !empty($raw_script_string) ) {

			if ( empty(self::$head_raw_scripts) ) {
				self::$head_raw_scripts = '';
			}

			self::$head_raw_scripts .= "\n" . $raw_script_string . "\n";

		}
	}

	/**
	 * Adder for script files that apply for front end footer
	 */
	public static function add_to_footer( $raw_script_string ) {
		if ( !empty($raw_script_string) ) {

			if ( empty(self::$footer_raw_scripts) ) {
				self::$footer_raw_scripts = '';
			}

			self::$footer_raw_scripts .= "\n" . $raw_script_string . "\n";

		}
	}

	/**
	 * Script printer for footer
	 */
	public function print_to_footer() {
		if ( ! empty( self::$footer_raw_scripts ) ) {

			echo "<!-- Front End Form Extension for ACF JavaScript for footer -->\n<script type=\"text/javascript\">\njQuery(function($) {";

			// Sanitize
			self::$footer_raw_scripts = self::sanitize_js( self::$footer_raw_scripts );

			echo self::$footer_raw_scripts . "});\n</script>\n";
		}
	}

	/**
	 * Script printer for head
	 */
	public function print_to_head() {
		if ( ! empty( self::$head_raw_scripts ) ) {

			echo "<!-- Front End Form Extension for ACF JavaScript head -->\n<script type=\"text/javascript\">\njQuery(function($) {";

			// Sanitize
			self::$head_raw_scripts = self::sanitize_js( self::$head_raw_scripts );

			echo self::$head_raw_scripts . "});\n</script>\n";
		}
	}

	/**
	 * Scrip sanitizer
	 */
	private function sanitize_js( $raw_script_string ) {
		$raw_script_string = wp_check_invalid_utf8( $raw_script_string );
		$raw_script_string = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $raw_script_string );
		$raw_script_string = str_replace( "\r", '', $raw_script_string );

		return $raw_script_string;
	}
}