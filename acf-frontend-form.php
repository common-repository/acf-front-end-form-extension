<?php
/**
 * Plugin Name: WPPC Front End Form Extension for ACF (Free)
 * Description: The Front End Form Plugin brings the awesomeness, and ease of use, of the Advanced Custom Fields Plugin for WordPress into the front end by generating a shortcode for each Field Group you create in ACF. But it's not just for creating a basic contact form. You can create quizes, interactive questionnaires, support request systems integrated with the Project Manager Plugin and more...
 * Author: WP Plugin Co.
 * Author URI: https://www.wppluginco.com
 * Plugin URI: https://wordpress.org/plugins/acf-front-end-form-extension/
 * Version: 1.0.20
 */
define( 'ACFFEF_FREE_VERSION', '1.0.20');

function acffef_dependency_active($dependency) {
	$acffef_dep_active = false;

	switch($dependency) {
		case 'acffef-premium':
			$acffef_dep_active = (defined('TPCP_ACFFEF_ROOT') && is_plugin_active( ltrim( strrchr( rtrim( TPCP_ACFFEF_ROOT, '/'), '/'), '/' ) . '/acf-frontend-form-premium.php' ));
			break;
		case 'cpt-ui':
			$acffef_dep_active = (defined('CPT_VERSION') && defined('CPTUI_WP_VERSION'));
			break;
		case 'acf':
			$acffef_dep_active = is_plugin_active('advanced-custom-fields/acf.php'); //(array_key_exists( 'acf/helpers/get_dir', $GLOBALS['wp_filter'] ) || array_key_exists( 'acf/get_valid_field', $GLOBALS['wp_filter'] ));
			break;
		case 'acf-pro':
			$acffef_dep_active = is_plugin_active('advanced-custom-fields-pro/acf.php');
			break;
		default:
			break;
	};

	return $acffef_dep_active;
}


require_once( dirname( __FILE__ ). '/classes/ACFFrontendFormAdminRawScripts.php' );
require_once( dirname( __FILE__ ). '/classes/ACFFrontendFormAdminNotices.php' );
require_once( dirname( __FILE__ ). '/classes/ACFFrontendFormActivation.php' );
require_once( dirname( __FILE__ ). '/classes/ACFFrontendFormPointers.php' );
require_once( dirname( __FILE__ ). '/classes/ACFFrontendForm.php' );

function force_load_acf_after_all() {
	ACFFrontendForm::instance( );
	ACFFrontendFormPointers::instance( );
	ACFFrontendFormAdminRawScripts::instance();
	ACFFrontendFormAdminNotices::instance();
	ACFFrontendFormActivation::instance();


	register_activation_hook( __FILE__,  'acffef_free_activation_hook');
	register_deactivation_hook(__FILE__,'acffef_free_deactivation_hook');
	register_uninstall_hook(__FILE__, 'acffef_free_uninstall_hook');

	function acffef_free_activation_hook() {

		delete_user_meta(get_current_user_id(), 'ACF_Frontend_Form_extension_admin_notice_cancelled');
		ACFFrontendFormActivation::intro_notice();
	}

	function acffef_free_deactivation_hook() {}

	function acffef_free_uninstall_hook() {

		delete_user_meta(get_current_user_id(), 'ACF_Frontend_Form_extension_admin_notice_cancelled');
	}

	$current_version = get_option( 'acffef_free_version' );
	if (!is_string($current_version) || $current_version != ACFFEF_FREE_VERSION) {
		update_option('acffef_free_version', ACFFEF_FREE_VERSION);
	}
	function add_custom_styles() {
			wp_enqueue_style( 'ACFFEF-custom', plugin_dir_url( __FILE__ ) .'assets/css/ACFFEF-custom.css ');
			wp_enqueue_script( 'ACFFEF-custom', plugin_dir_url( __FILE__ ) .'assets/js/ACFFEF-custom.js','','',true );
	}
	add_action( 'wp_enqueue_scripts', 'add_custom_styles' );
}

add_action( 'plugins_loaded', 'force_load_acf_after_all', 10 );
