<?php
/**
 * Front End Form Extension for ACF Class
 *
 * The main plugin class
 *
 * @package  Front End Form Extension for ACF
 * @version  1.1
 */
$plugin_root = dirname( dirname( __FILE__ ) );
$plugin_url  = plugins_url( '', dirname( __FILE__ ) );
defined( 'ACFFF_DIR' ) or define( 'ACFFF_DIR', $plugin_root );
defined( 'ACFFF_URL' ) or define( 'ACFFF_URL', $plugin_url );

class ACFFrontendForm {
	/**
	 * Instance container
	 * @var ACFFrontendForm
	 */
	private static $instance;

	/**
	 * Global Variables
	 */
	public $cpmIntegration;
	public $premium;
	public $acfdisplay;
	public $val	=	array();
	public $pageHook;

	/**
	 * CONSTANTS
	 */
	const BASEURL = ACFFF_URL;
	const BASEDIR = ACFFF_DIR;
	const SLUG    = 'ACF_Frontend_Form';
	const VERSION = '1.0.0';

	/**
	 * Cloning is not allowed
	 */
	private function __clone( ) { }

	/**
	 * Serialization is not allowed either
	 */
	private function __serialize( ) { }

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
	 * Initialize this object and add all our wordpress hooks here
	 */
	private function __construct( ) {
		add_action( 'admin_menu', array( $this,'adminMenu' ) );
		add_action( 'admin_notices', array( $this,'adminNotices' ) );
		add_action('admin_enqueue_scripts', array($this, 'loadScript'));
        add_action('wp_ajax_remove_admin_notice', array($this, 'removeAdminNotice'));
		add_shortcode( 'acf_form', array( $this, 'displayForm' ) );

		if( get_option( "acffef/update_message" ) === false || get_option( "acffef/notification" ) === false || get_option( "acffef/to" ) === false || get_option( "acffef/subject" ) === false || get_option( "acffef/message" ) === false || get_option( "acffef/cpmintegrate" ) === false )
		{

			add_option( "acffef/update_message", "Thank you for your submission. Someone will be in touch with you as soon as possible.", null, "no" );
			add_option( "acffef/notification", 1, null, "no" );
			add_option( "acffef/to",  get_bloginfo("admin_email"), null, "no" );
			add_option( "acffef/subject", "Request to be Contacted", null, "no" );
			add_option( "acffef/message", "Someone has submitted a request to be contacted through your website. <a href='#' target='_blank'>Click here to review »</a>", null, "no" );
			add_option( "acffef/gbut", "Submit", null, "no" );
			add_option( "acffef/cpmintegrate", 1, null, "no" );
		}
		if( !is_admin() ){

			if(class_exists('acf'))
			    add_action( 'wp_loaded', 'acf_form_head' );

			add_action( 'wp_print_styles', array( $this, 'unregisterStyles' ), 100 );

			if(get_option("acffef/notification") == 1)
				add_action( 'notifyACFFE', array( $this, 'sendEmailACF' ) );

			add_filter('acf/pre_save_post' , array( $this, 'addNewPost' ) );
		}
	}



	/** acf-field-group
	 * Setup the things we need in the admin
	 */
	public function adminMenu( ) {
		add_filter( 'manage_edit-acf_columns', array( $this, 'addCustomColumnHeads' ), 20 );
		add_action( 'manage_acf_posts_custom_column', array( $this, 'addCustomColumnContents' ), 20, 2 );
		add_filter( 'manage_edit-acf-field-group_columns', array( $this, 'addCustomColumnHeads' ), 20 );
		add_action( 'manage_acf-field-group_posts_custom_column', array( $this, 'addCustomColumnContents' ), 20, 2 );
		$this->pageHook = add_options_page( 'Front End Form Extension for ACF Plugin Settings', 'ACFFEF', 'activate_plugins', 'acffef_menu', array( $this, 'pluginMenuACF' ));
		add_action( 'admin_print_styles-' . $this->pageHook, function(){ wp_enqueue_style( 'acffef_styles' ); } );
		add_action( 'admin_print_styles-' . $this->pageHook, function(){ wp_enqueue_style( 'jquery_ui' ); } );
		add_action( 'admin_print_scripts-' . $this->pageHook, function(){ wp_enqueue_script( 'jquery-ui-core' ); } );
		add_action( 'admin_print_scripts-' . $this->pageHook, function(){ wp_enqueue_script( 'jquery-ui-accordion' ); } );

		add_action('load-'.$this->pageHook, array($this, 'onLoadPage'));
		add_filter('screen_layout_columns', array($this, 'on_screen_layout_columns'), 10, 2);

	}

    //for WordPress 2.8 we have to tell, that we support 2 columns !
	function on_screen_layout_columns($columns, $screen) {
		if ($screen == $this->pageHook) {
			$columns[$this->pageHook] = 2;
		}
		return $columns;
	}

    public function onLoadPage() {

        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');
        add_meta_box('acffef_free_settings_meta_box_id', 'Front End Form Extension for ACF Settings', array($this, 'acffef_free_settings_meta_box_container'), $this->pageHook, 'normal', 'core', null);
        add_meta_box( 'plugin_info_meta_box_id', 'About WPPluginCo.com', array($this, 'plugin_info_meta_box_container'), $this->pageHook, 'side', 'core', null );
        add_meta_box('other_plugins_meta_box_id', 'Other Plugins by WPPluginCo.com', array($this, 'other_plugins_info_meta_box_container'), $this->pageHook, 'side', 'core', null);
        add_meta_box('related_plugins_meta_box_id', 'Related Plugins', array($this, 'related_plugins_info_meta_box_container'), $this->pageHook, 'side', 'core', null);
    }

	public function adminNotices( ) {
		wp_register_style( 'acffef_styles', plugins_url( '../assets/css/acf.css', __FILE__) );
		wp_register_style( 'jquery_ui', plugins_url( '../assets/css/jquery-ui.css', __FILE__) );

		if ( (!acffef_dependency_active('cpt-ui') || !acffef_dependency_active('acf')) && !get_user_meta(get_current_user_id(), 'ACF_Frontend_Form_extension_admin_notice_cancelled', true) ) {
		    $notice_message = '';
			?>
			<div data-dismissible="disable-done-notice" class="updated notice is-dismissible">
				<p>Front End Form Extension for ACF requires that you install and activate all of these plugins:
				<?php if ( !acffef_dependency_active('cpt-ui') ) $notice_message .= '<a class="thickbox" href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=custom-post-type-ui&TB_iframe=true&width=600&height=550').'">Custom Post Type UI Plugin</a><br>'; ?>
				<?php if ( !acffef_dependency_active('acf') ) $notice_message .= ", <a href='http://www.advancedcustomfields.com/'>Advanced Custom Fields</a>"; ?>
				<?php printf('%s', $notice_message);?>
				</p>
				<p>If these plugin(s) are installed please activate them <a href='plugins.php'>here</a></p>
				<p>To use Front End Form Extension for ACF you must install the <?php echo '<a class="thickbox" href="'.admin_url('plugin-install.php?tab=plugin-information&plugin=custom-post-type-ui&TB_iframe=true&width=600&height=550').'">Custom Post Type UI Plugin</a>'?>. If you don't understand why then just trust us. :) If you do understand why and just want to use a different Plugin you can skip this but we don't recommend it.</p>
			</div>
			<?php
		}

		if ( is_plugin_active( 'project-manager-by-tpc/cpm.php' ) ) {
			$this->cpmIntegration = true;
		} else {
			$this->cpmIntegration = false;
			update_option( "acffef/cpmintegrate", 0 );
		}

		if ( acffef_dependency_active('acffef-premium') )
			$this->premium = true;
	}

    /*
     *
     */
    public function removeAdminNotice()
    {
        update_user_meta(get_current_user_id(), 'ACF_Frontend_Form_extension_admin_notice_cancelled', true);
        return;
    }

    public function loadScript()
    {
        wp_enqueue_script('remove-notice', plugins_url('js/remove.js', __FILE__), array('jquery', 'common'), false, true);
        wp_localize_script(
				'remove-notice',
				'remove',
				array(
					'nonce' => wp_create_nonce( 'remove' ),
				)
			);
    }

	/**
	 * Displays the custom column head
	 * @param Array $columns 	List of table heads
	 */
	public function addCustomColumnHeads( $columns ) {
		$neworder = array( );
		foreach( $columns as $id => $name ) {
			$neworder[ $id ] = $name;
			if( $id == 'cb' ) {
				$neworder[ 'id' ] = 'Group ID';
				$neworder[ 'sc' ] = 'Short Code';
			}
		}
		return $neworder;
	}



	/**
	 * Displays the custom column content
	 * @param String 	$name
	 * @param Integer 	$id
	 */
	public function addCustomColumnContents( $name, $id ) {
		if( $name == 'id' ) {
			echo $id;
		}
		if( $name == 'sc' ) {
			echo '[acf_form group_id="'.$id.'" create_new_post="true" post_type="example-type"]';
		}
	}



	/**
	 * Unregister unnecessary styles that breaks the form
	 */
	public function unregisterStyles( ) {
		wp_deregister_style( 'wp-admin' );
	}

	/**
	 * Generate Shortcodes for each field
	 */

	 public function generate_scodes( $atts ) {

		$atts = shortcode_atts( array(

				'field_name' => "",

				'val' => $this->val

			), $atts );

			$fn	=	$atts['field_name'];

			return $atts['val'][$fn];

			// return print_r($atts['val']);

			//return "tx a lot";

		}


	/**
	 * Display an acf form
	 * @param  Array $atts 	List of Shortcode Attributes
	 * @return String       HTML string
	 */
	 /**
	 * [Edited] Modified ACF Form to create a new post once the form is submitted.
	 */
	public function displayForm( $atts ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		ob_start( );
		$atts = shortcode_atts( array(
				'group_id' => "",
				'create_new_post' => "false",
				'post_type' => "post"
			), $atts );

		$groups = explode( ",", $atts[ 'group_id' ] );
		$groups = array_map( 'trim', $groups );

		if ( acffef_dependency_active('acffef-premium'))
		{
			$options = array(
				'field_groups' => $groups,
			);
			if(strlen(get_option("acffef/but-$groups[0]")) > 0)
				$options['submit_value'] = get_option("acffef/but-$groups[0]");
			else
				$options['submit_value'] = get_option("acffef/gbut");

			if(strlen(get_option("acffef/umessages-$groups[0]")) > 0)
				$options['updated_message'] = get_option("acffef/umessages-$groups[0]");
			else
				$options['updated_message'] = get_option("acffef/update_message");
		}
		else
			$options = array(
				'field_groups' => $groups,
				'submit_value' => 'Submit',
				'updated_message' => stripslashes(get_option("acffef/update_message")),
			);

		if(strtolower($atts[ 'create_new_post' ]) == "true")
			$options = array_merge(array('post_id' => "new-".$atts[ 'post_type' ]), $options);

        $options['form_attributes']['enctype'] = 'multipart/form-data';

		// Set a form ID
		$options['form_attributes']['id'] = 'acffef_' . $atts['group_id'];
		$options['updated_message'] = '<div class="acffef-thankyou-msg" id="thankyou-msg-'.$atts['group_id'].'">' . $options['updated_message'] . '</div>';
		$return_link = add_query_arg( 'submitted', 'form-' . $atts['group_id'], "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
		$return_link = add_query_arg( 'updated', 'true', $return_link );
		$options['return'] = $return_link;

		acf_form($options);
		wp_enqueue_script( 'custom-script', plugins_url('../assets/js/custom_script.js', __FILE__), array( 'jquery' ) );
		return ob_get_clean( );
	}

	public function addNewPost( $post_id ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		global $wpdb;
		$temppost_id = $post_id;
		$post_id = explode( "-", $post_id);
		if( $post_id[0] != 'new' )
			return $post_id;

		if( count($post_id) > 2 ){
			array_shift( $post_id );
			$post_id[1] = implode( "-",$post_id );

		}

		$iterable_fields = array();
		if ( acffef_dependency_active('acf-pro') ) {
			$iterable_fields = $_POST['acf'];
		} else {
			$iterable_fields = $_POST['fields'];
		}
		foreach($iterable_fields as $key => $value)
		{
			$currentacf = $key;
			// Get fields and make their shortcodes
			if ( acffef_dependency_active('acffef-premium') )
			{

				$t	=	get_field_object($key);

			    add_shortcode( 'acffef', array( $this, 'generate_scodes' ) );

				$this->val[$t['name']]	=	$value;

			}
		}
		if ( acffef_dependency_active('acf-pro') ) {
			$currentacf = $GLOBALS['acf_form']['field_groups'][0];
		}
		$sql_query = "
			SELECT $wpdb->postmeta.post_id
			FROM $wpdb->postmeta
			WHERE $wpdb->postmeta.meta_key = '$currentacf'
		";
		$results = $wpdb->get_results($sql_query, ARRAY_A);
		if ( acffef_dependency_active('acf-pro') ) {
			$this->acfdisplay = $GLOBALS['acf_form']['field_groups'][0];
		} else {
			$this->acfdisplay = $results[0]["post_id"];
		}
		$subject = get_option("acffef/subject");
		// Form specific post title
		$custom_post_title = apply_filters( 'acffef_premium/replace_fieldcodes', get_option("acffef/post-title-" . $this->acfdisplay) );

		// Get fields shortcode and use that in title
		if ( acffef_dependency_active('acffef-premium') )
		{
			$subject	=	stripslashes_deep($subject);

			if (strpos($subject,'field_name') !== false) {

				$subject = do_shortcode( $subject );

			}

		}

		$post = array(
			'post_status'  => 'publish',
			'post_title'  => ($custom_post_title == '' ? $subject : $custom_post_title),
			'post_type'  => $post_id[1]
		);
		$post_type = $post_id[1];
		$post_id = wp_insert_post( $post );
		$_POST['return'] = add_query_arg( array('post_id' => $post_id, '?' => '#message'), $_POST['return'] );



		do_action( 'notifyACFFE' );

		/*
		 * Integration with Project Manager
		 */
		if(get_option("acffef/cpmintegrate") == 1)
		{
			$quickQuery = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s AND post_type = project', sanitize_title_with_dashes($post_type));
			$projectID = $wpdb->get_var( $quickQuery );

			$cpmController = new CPM_Project();
			$_POST['project_name'] = ucwords($post_type);
			$_POST['project_description'] = "Automatically created by Front End Form Extension for ACF";
			if ( empty($projectID) )
				$projectID = $cpmController->create();

			$cpmController = new CPM_Task();
			$_POST['tasklist_name'] = "Support Requests";
			$_POST['tasklist_detail'] = "Automatically created by Front End Form Extension for ACF";
			$cpmController->add_list($projectID);
		}

		return $post_id;
	}
	public function sendEmailACF( ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		//$to = get_option("acffef/to");
        add_filter( 'wp_mail_content_type', function($content_type){
            return 'text/html';
        });

		$to = explode(',', get_option("acffef/to"));

		$subject = get_option("acffef/subject");

		// If email subject has shortcode then use it in email subject
		if ( acffef_dependency_active('acffef-premium') )
		{
			if (strpos($subject,'field_name') !== false) {
				$subject = do_shortcode( $subject );
			}
		}

		if ( acffef_dependency_active('acffef-premium') )
		{
			if(strlen(get_option("acffef/recp-".$this->acfdisplay))>0)
				$to = explode(',', get_option("acffef/recp-".$this->acfdisplay));
			if(strlen(get_option("acffef/subj-".$this->acfdisplay))>0)
				$subject =get_option("acffef/subj-".$this->acfdisplay);
			if(strlen(get_option("acffef/messages-".$this->acfdisplay))>0){
				$message =	'<table><tr><td colspan="5">';
				$message .=	'<b>'.get_option("acffef/messages-".$this->acfdisplay).'</b>';//implode('-',$_POST['fields']);
				$message .= '</td></tr><tr><td>&nbsp;</td></tr>';
				if (strpos($message,'field_name') !== false) {
					$pattern = get_shortcode_regex();
					$msg	=	stripslashes_deep($message);
					preg_match_all('/'.$pattern.'/uis', $msg, $matches);
					$message = strip_shortcodes( $message );
					foreach($matches[0] as $vals)	{$message .= '<br>'.do_shortcode( stripslashes_deep($vals) );}
				} else {
					// ACF Pro specific setting
					if ( acffef_dependency_active('acf-pro') ) {
						$iterable_fields = $_POST['acf'];
					} else {
						$iterable_fields = $_POST['fields'];
					}

					foreach( $iterable_fields as $k => $v )
					{
						if(!empty($v)){
							$fo	= get_field_object($k);
							if($v	&&	is_array($v)) {
								$message .=	'<tr><td>'.$fo['label'].'</td>';
								foreach( $v as $vk => $vv ){
									if($v	&&	is_array($v)) {
										foreach( $vv as $vvk => $vvv ){
											$message .=	'<td>'.$vvv.'</td>';
										}
									} else
										$message .=	'<tr><td>'.$fo['label'].'</td><td>'.$vv.'</td></tr>';
								}
								$message .=	'</tr>';
							} else
								$message .=	'<tr><td>'.$fo['label'].'</td><td>'.$v.'</td></tr>';
						}
					}
				}
				$message .=	'</table>';
			}
			else
				$message = get_option("acffef/message");
		}
		else
			$message = get_option("acffef/message");
			if ( acffef_dependency_active('acffef-premium') )
			{
				if (strpos($message,'field_name') !== false) {
					$pattern = get_shortcode_regex();
					$msg	=	stripslashes_deep($message);
					preg_match_all('/'.$pattern.'/uis', $msg, $matches);
					$message = strip_shortcodes( $message );
					$message .= '<br>'.do_shortcode( stripslashes_deep($matches[0][0]) );
					$message .= '<br>'.do_shortcode( stripslashes_deep($matches[0][1]) );
				}
			}
			add_filter( 'wp_mail_content_type', function($content_type){
				return 'text/html';
			});

      $emails_address_list = array();
			foreach($to as $to_email_address) {
				$to_email_address = trim($to_email_address);
        $emails_address_list[] = $to_email_address;
			}

			// Parse field codes inserted via email configuration
			if ( acffef_dependency_active('acffef-premium') ) {
				$message = apply_filters( 'acffef_premium/replace_fieldcodes', $message);
				$subject = apply_filters( 'acffef_premium/replace_fieldcodes', $subject);
			}

      wp_mail($emails_address_list, $subject, $message );

      remove_filter( 'wp_mail_content_type', function($content_type){
          return 'text/html';
      });
	}
	public function pluginMenuACF( ) {

	    global $screen_layout_columns;
		foreach($_POST as $key => $value)
			if($key != "acffef/notification")
			{
				update_option($key, $value);
				if(isset( $_POST["acffef/notification"] ))
					update_option("acffef/notification", 1);
				else
					update_option("acffef/notification", 0);

				if(isset( $_POST["acffef/cpmintegrate"] ))
					update_option("acffef/cpmintegrate", 1);
				else
					update_option("acffef/cpmintegrate", 0);
			}
		?>

		<div id="acffef-meta-box-general" class="wrap">

			<form id="acffef_form" method="POST">
			<?php
                wp_nonce_field('acffef-meta-box-general');
                wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
                wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
             ?>
			<input type="hidden" name="action" value="save_howto_metaboxes_general" />
			<h2> Front End Form Extension for ACF Settings </h2>
			<div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content" style="position: relative">
                    <?php
                        do_meta_boxes($this->pageHook, 'normal', '');
                    ?>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <?php
                        do_meta_boxes($this->pageHook, 'side', '');
                    ?>

                </div>
            </div>
        </div>
			</form>
		</div>
		<script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                // close postboxes that should be closed
                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                // postboxes setup
                postboxes.add_postbox_toggles('<?php echo $this->pageHook; ?>');
            });
            //]]>
	    </script>
		<script>
			$ = jQuery;
			<?php
				$notification_current_state = get_option("acffef/notification");
				$integration_current_state = get_option("acffef/cpmintegrate");
			?>
			$(document).ready(function(){
				acffef_notification = <?php echo $notification_current_state; ?>;
				acffef_integration = <?php echo $integration_current_state ?>;
				$("#acf_activate_notifications").change(function(e){
					if($("#acf_activate_notifications").attr( "checked" ))
						toggleACFEmailSettings( "inline" );
					else
						toggleACFEmailSettings( "none" );
				});
				$(".acffefTooltip").on("mouseover",function(e){
					acffefPOS = $( this ).offset();
					$("<div class='acffefTooltipContainer' style='top:" + (acffefPOS.top) + "px ;left:" + (e.pageX - acffefPOS.left) + "px '>" + $(this).attr("alt") + "</div>").insertAfter(this);
					$(".acffefTooltipContainer").fadeIn();
				});
				$(".acffefTooltip").on("mouseout",function(e){
					$(".acffefTooltipContainer").fadeOut(function(){$(this).remove();});
				});
				$("#acffef_form").submit(function(e){

					if(isEmail($("#acf_email_to").val()) === false  && $("#acf_activate_notifications").is(':checked') != false )
					{
						alert("Please check the email field, data seems to be invalid. Please use comma to separate multiple emails");
						e.preventDefault();
					}
				});
				if(acffef_notification == 1)
				{
					$("#acf_activate_notifications").attr( "checked", true );
					toggleACFEmailSettings( "inline" );
				}
				else
				{
					$("#acf_activate_notifications").attr( "checked", false );
					toggleACFEmailSettings( "none" );
				}
				if(acffef_integration == 1)
					$("#acf_activate_integration").attr( "checked", true );
				else
					$("#acf_activate_integration").attr( "checked", false );

				function isEmail(email)
				{
					var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
					var acfEmailsList = email.split(",");
					console.log(acfEmailsList);
					acfValidEmails = true;
					for (i = 0 ; i<acfEmailsList.length ; i++) {
						acfEmailsList[i] = acfEmailsList[i].trim();
						acfValidEmails = acfValidEmails && regex.test(acfEmailsList[i]);
					}
					return acfValidEmails;
				}
				function toggleACFEmailSettings( state )
				{
					$(".acf_email_settings").css( "display",state );
				}
			});
		</script>
		<?php
	}

    public function plugin_info_meta_box_container() {

	$im_plugin_extensions = array(
	);
	$im_plugins_list = array(
		'Bulk Photo And Product Importer Plugin For Wordpress' => 'https://wordpress.org/plugins/bulk-photo-to-product-importer-extension-for-woocommerce/',
		'Premium Bulk Photo To Product Importer Extension For WooCommerce' => 'https://www.wppluginco.com/product/premium-bulk-photo-to-product-importer-extension-for-woocommerce',
		'ACF Front End Form Plugin' => 'https://www.wppluginco.com/product/acf-front-end-form-plugin',
		'CRM Plugin For Wordpress' => 'https://www.wppluginco.com/product/crm-plugin-for-wordpress',
		'Custom Pointers Plugin For Wordpress' => 'https://www.wppluginco.com/product/custom-pointers-plugin-for-wordpress'
	);
	?>
			<table class="ptp-input widefat" style="border: none;">
				<tbody>
					<tr>
						<td>
							This Plugin was created by <a href="https://www.wppluginco.com/">WP Plugin Co.</a> and <a href="https://www.wppluginco.com/services/wordpress-plugins">you can find more of our Plugins, or request a custom one, here </a>.

						</td>
					</tr>
				</tbody>
			</table>
        <?php

	}


	public function related_plugins_info_meta_box_container() {
	   echo "";
	}

	public function other_plugins_info_meta_box_container()
    {

        $im_plugin_extensions = array();

        $im_plugins_list = array(
            'Bulk Photo And Product Importer Plugin For Wordpress' => 'https://wordpress.org/plugins/bulk-photo-to-product-importer-extension-for-woocommerce/',
            'Premium Bulk Photo To Product Importer Extension For WooCommerce' => 'https://www.wppluginco.com/product/premium-bulk-photo-to-product-importer-extension-for-woocommerce',
            'ACF Front End Form Plugin' => 'https://www.wppluginco.com/product/acf-front-end-form-plugin',
            'CRM Plugin For Wordpress' => 'https://www.wppluginco.com/product/crm-plugin-for-wordpress',
            'Custom Pointers Plugin For Wordpress' => 'https://www.wppluginco.com/product/custom-pointers-plugin-for-wordpress'
        );

        ?>
        <table class="ptp-input widefat" style="border: none;">
            <tr>
                <td>
                    <?php
                    if (count($im_plugin_extensions) > 0) {
                        ?>
                        <ul>
                            <?php
                            foreach ($im_plugin_extensions as $title => $link) {
                                ?>
                                <li><a href="<?php echo $link ?>"><?php echo $title ?></a></li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                    <?php
                    if (count($im_plugins_list) > 0) {
                        ?>
                        <ul style="list-style: circle inside;">
                            <?php
                            foreach ($im_plugins_list as $title => $link) {
                                ?>
                                <li><a href="<?php echo $link ?>"><?php echo $title ?></a></li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?php

    }
    public function acffef_free_settings_meta_box_container() {

        ?>
        <table class="form-table" style="display:inline">


            <?php if ( $this->premium != true ) { ?>

                <tr>
                    <th scope="row">General Message to be displayed after submitting the form.</th>
                    <td><?php wp_editor( stripslashes_deep(get_option("acffef/update_message")), "acffef_update_message", array("textarea_name" => "acffef/update_message", "media_buttons" => false, "teeny" => true, "textarea_rows" => 5) ); ?></td>
                </tr>
                <?php
            }
            if($this->cpmIntegration == true)
            {
                ?>
                <tr>
                    <th scope="row">Activate Integration with Project Manager. </th>
                    <td><input type="checkbox" id="acf_activate_integration" name="acffef/cpmintegrate"><img alt="When this is activated a Task will be created, in the Project Manager. It will be added to a Task List called 'Support Requests' or create one if it doesn't already exist. It will be added to the Project that is based on the ACF Field you specified in Settings." class="acffefTooltip accffeInformation" align="top" width="18" src="<?php echo plugins_url( '../assets/img/circle_info.png' , __FILE__ ); ?>"></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <th scope="row">Send an email notification with data records once form has been updated.</th>
                <td><input type="checkbox" id="acf_activate_notifications" name="acffef/notification"></td>
            </tr>
        </table>
        <table class="form-table acf_email_settings">
            <?php if ( $this->premium != true ) { ?>
                <tr>
                    <th scope="row">Email Settings</th>
                </tr>
                <tr>
                    <th scope="row">Send to whom?</th>
                    <td><input size="50" type="text" placeholder="Email Address" name="acffef/to" id="acf_email_to" value="<?php echo get_option("acffef/to"); ?>"> Use comma to separate multiple emails</td>
                </tr>
                <tr>
                    <th scope="row">Email Subject</th>
                    <td><input size="50" type="text" placeholder="Subject" name="acffef/subject" value="<?php echo stripslashes_deep(get_option("acffef/subject")); ?>"></td>
                </tr>
            <?php } ?>
            <tr>
                <th scope="row">
                    <?php
                    if ( $this->premium == true )
                        echo "Individual Settings";
                    else
                        echo "Email Message";
                    ?>
                </th>
                <td>
                    <?php
                    if ( $this->premium == true ){
                        do_action("acffe_premium_groupmessage");
                    }
                    else{
                        ?>
                <?php wp_editor( stripslashes_deep(get_option("acffef/message")), "acffef_message", array("textarea_name" => "acffef/message", "media_buttons" => false, "teeny" => true, "textarea_rows" => 5) ); ?>

                        <?php
                    }
                    ?>

                </td>
            </tr>
            </div>
            <tr>
                <td colspan="2"><input type="submit" value="Save Changes" class="btn btn-primary button button-primary button-large"></td>
            </tr>
        </table>
        <?php
    }

}
