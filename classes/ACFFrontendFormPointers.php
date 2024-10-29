<?php
/**
 * Adds and controls pointers for contextual help/tutorials. Based on WooCommerce Admin Pointers class
 *
 * @author   Tafhim Ul Islam (tafhim.ul.islam@gmail.com)
 * @category Admin
 * @package  Front End Form Extension for ACF
 * @version  1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACFFEF_Admin_Pointers Class
 */
class ACFFrontendFormPointers {
	/**
	 * Instance container
	 * @var ACFFrontendFormPointers
	 */
	private static $instance;

	/**
	 * Version string for notices
	 */
	private static $version;

	/**
	 * Option key for pointers viewing state
	 */
	private static $pointers_shown_option = 'acffef_tutorial_shown';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_pointers_for_screen' ) );
		self::$pointers_shown_option = 'acffef_tutorial_shown';
		self::$version = '1.0.7';
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
	 * Setup pointers for screen.
	 */
	public function setup_pointers_for_screen() {
		$screen = get_current_screen();

		switch ( $screen->id ) {
			case 'dashboard' :
				$this->create_acffef_tutorial();
			break;
		}
	}

	/**
	 * Activate tutorials
	 */
	public static function activate_tutorials() {
		update_option(self::$pointers_shown_option, false);
	}

	/**
	 * Deactivate tutorials
	 */
	public static function deactivate_tutorials() {
		update_option(self::$pointers_shown_option, true);
	}

	/**
	 * Get tutorial shown state
	 */
	private function tutorials_shown() {
		return get_option(self::$pointers_shown_option);
	}

	/**
	 * Pointers for creating a front end form.
	 */
	public function create_acffef_tutorial() {

		if ( ! current_user_can( 'manage_options' ) || $this->tutorials_shown() ) {
			return;
		}

		// These pointers will chain - they will not be shown at once.
		$pointers = array(
			'pointers' => array(
				'acffef-settings' => array(
					'target'       => "#menu-settings",
					'next'         => 'acffef-fields',
					'options'      => array(
						'content'  => 	'<h3>' . esc_html__( 'ACFFEF Settings', 'tpc-acffef' ) . '</h3>' .
										'<p>' . esc_html__( 'The ACFFEF menu can be found here. This is where you can configure who the submissions are emailed to, the content of the messages delivered to those receiving emails and more.', 'tpc-acffef' ) . '</p>',
						'position' => array(
							'edge'  => 'left',
							'align' => 'right'
						)
					)
				),
				'acffef-fields' => array(
					'target'       => "#toplevel_page_edit-post_type-acf",
					'next'         => 'acffef-support',
					'options'      => array(
						'content'  => 	'<h3>' . esc_html__( 'Creating forms', 'tpc-acffef' ) . '</h3>' .
										'<p>' . esc_html__( 'To create a new form you can create a Field Group as you normally would using ACF but you\'ll see some new options such as the short code to display the form and whether or not to save the submission.', 'tpc-acffef' ) . '</p>',
						'position' => array(
							'edge'  => 'left',
							'align' => 'right'
						)
					)
				),
				'acffef-support' => array(
					'target'       	=> "#menu-settings",
					'next' 		=> '',
					'options'  	=> array(
						'content'  => 	'<h3>' . esc_html__( 'Support', 'tpc-acffef' ) . '</h3>' .
										'<p><a href="http://theportlandcompany.com/forums">' . esc_html__( 'If you ever need support refer to our forums and we\'ll get back to you as soon as possible.' , 'tpc-acffef' ) . '</a></p>' .
										'<p><a href="http://theportlandcompany.com/">' . esc_html__( 'And don\'t forget that we can assist you with other things too like website development, custom WordPress Plugin development.', 'tpc-acffef' ) . '</a></p>' ,
						'position' => array(
							'edge'  => 'left',
							'align' => 'right'
						)
					)
				),
				'acffef-dummy' => array(
					'target'       => "#menu-dashboard",
					'next'         => 'acffef-dummy',
					'options'  => array(
						'content'  => 	'<h3>' . esc_html__( 'Support', 'tpc-acffef' ) . '</h3>' .
										'<p><a href="http://theportlandcompany.com/forums">' . esc_html__( 'If you ever need support refer to our forums and we\'ll get back to you as soon as possible.' , 'tpc-acffef' ) . '</a></p>' .
										'<p><a href="http://theportlandcompany.com/">' . esc_html__( 'And don\'t forget that we can assist you with other things too like website development, custom WordPress Plugin development.', 'tpc-acffef' ) . '</a></p>' ,
						'position' => array(
							'edge'  => 'left',
							'align' => 'right'
						)
					)
				)
			)
		);


		$this->enqueue_acffef_pointers( $pointers );

		// Tutorials will not be shown next
		self::deactivate_tutorials();
	}

	/**
	 * Enqueue pointers and add script to page.
	 * @param array $pointers
	 */
	public function enqueue_acffef_pointers( $pointers ) {
		$pointers = json_encode( $pointers );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );

		ACFFrontendFormAdminRawScripts::add_to_footer( "
			jQuery( function( $ ) {
				var acffef_pointers = {$pointers};

				setTimeout( init_acffef_pointers, 800 );

				function init_acffef_pointers() {
					$.each( acffef_pointers.pointers, function( i ) {
						show_acffef_pointer( i );
						return false;
					});
				}

				function show_acffef_pointer( id ) {
					var pointer = acffef_pointers.pointers[ id ];
					var options = $.extend( pointer.options, {
						close: function() {
							if ( pointer.next ) {
								show_acffef_pointer( pointer.next );
							}
						}
					} );
					var this_pointer = $( pointer.target ).pointer( options );
					this_pointer.pointer( 'open' );

					if ( pointer.next_trigger ) {
						$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
							setTimeout( function() { this_pointer.pointer( 'close' ); }, 400 );
						});
					}
				}
			});
		" );
	}

}
