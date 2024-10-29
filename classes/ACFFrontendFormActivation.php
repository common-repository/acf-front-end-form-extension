<?php
class ACFFrontendFormActivation {
	/**
	 * Instance for the object
	 */
	private static $instance;
	private static $plugin_name = 'Front End Form Extension for ACF';
	private static $plugin_option_prefix = 'acfffef_free_';


	/**
	 * Constructor
	 */
	public function __construct() {
		self::$plugin_name = 'Front End Form Extension for ACF';
		self::$plugin_option_prefix = 'acfffef_free_';
		add_action( 'update_option_acffef_free_version', array($this, 'intro_notice') );
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

	private static function option_key( $key_suffix ) {
		return self::$plugin_option_prefix . $key_suffix;
	}

	public static function intro_notice() {
		ACFFrontendFormAdminNotices::add ( array(
			'key' => self::option_key( 'intro_notice' ),
			'notice' => '<div class="updated"><p>' . 'Thank you for using ' . self::$plugin_name . '. For support please post in our <a href="http://www.theportlandcompany.com/forums/">forums</a> link to. You may also be interested in our other <a href="http://theportlandcompany.com/">Plugins</a> or services including <a href="http://theportlandcompany.com/">Website Development</a>, <a href="http://theportlandcompany.com/">Custom WordPress Plugin Development</a>, <a href="http://theportlandcompany.com/">Search Marketing</a> and Brand Management.' . '</p></div>',
			'persist' => false
		) );
	}

}