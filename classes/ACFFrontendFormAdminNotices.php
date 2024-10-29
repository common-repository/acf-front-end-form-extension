<?php

/**
 * Front End Form Extension for ACF | Admin Notices class
 * @author Tafhim Ul Islam (The Portland Company)
 */

class ACFFrontendFormAdminNotices {
	/**
	 * Instance for the object
	 */
	private static $instance;
	private static $notices;
	private static $plugin_name = 'Front End Form Extension for ACF';
	private static $plugin_option_prefix = 'acffef_free_';

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$notices = array();
		self::$plugin_name = 'Front End Form Extension for ACF';
		self::$plugin_option_prefix = 'acfffef_free_';
		add_action( 'admin_notices', array( $this, 'show_all' ) );
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

	/**
	 * Add a notice
	 */
	public static function add( $notice ) {
		if ( isset($notice['key']) && isset($notice['notice']) && is_string($notice['key']) ) {
			self::$notices = get_option( self::option_key( 'admin_notices' ) );
			if (!is_array(self::$notices)) self::$notices = array();
			self::$notices[ $notice['key'] ] = $notice;

			update_option( self::option_key( 'admin_notices' ), self::$notices );
		}
	}

	/**
	 * Remove notice
	 */
	public static function remove( $notice_key ) {
		if ( isset($notice_key) && is_string($notice['key']) ) {
			self::$notices = get_option( self::option_key( 'admin_notices' ) );
			if (!is_array(self::$notices)) self::$notices = array();
			if ( isset(self::$notices[ $notice['key'] ]) )
				unset(self::$notices[ $notice['key'] ]);

			update_option( self::option_key( 'admin_notices' ), self::$notices );
		}
	}

	/**
	 * Hook all notices
	 */
	public function show_all() {
		self::$notices = get_option( $this->option_key( 'admin_notices' ) );
		if (is_array( self::$notices )) {
			$notices_cache = self::$notices;
			foreach($notices_cache as $notice) {

				echo $notice[ 'notice' ];
				
				if ( ! $notice['persist'] ) {
					unset( self::$notices[ $notice['key'] ] );
				}
			}

			update_option( $this->option_key( 'admin_notices' ), self::$notices );
			self::$notices = get_option( $this->option_key( 'admin_notices' ) );

		}
	}



}