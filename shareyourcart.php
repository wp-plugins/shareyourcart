<?php
/*
 * 
Plugin Name: ShareYourCart
Plugin URI: http://www.shareyourcart.com
Description: ShareYourCart™ helps you get more customers by motivating satisfied customers to talk with their friends about your products.
Version: 1.3.3
Author: Barandi Solutions
Author URI: http://www.barandisolutions.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
require_once(dirname(__FILE__).'/shareyourcart-sdk.php');

global $shareyourcart_secret_key, $shareyourcart_db_version, $plugin_path;

$plugin_path = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));

$activation_status = FALSE;
$shareyourcart_db_version = "1.0";
$shareyourcart_secret_key = 'f03f6ef2-9286-11e0-b1d5-005056867abd';

// Prevents the button from being showed twice
$shareyourcart_button_showed = false;

$VERTICAL_BUTTON_NORMAL = "vertical";
$VERTICAL_BUTTON_LEFT = "vertical-left";

//hook the actions
add_action('activate_'.plugin_basename(__FILE__), 'shareyourcart_activate');
add_action('deactivate_'.plugin_basename(__FILE__), 'shareyourcart_deactivate');
add_action('wp_print_styles', 'add_shareyourcart_style');
add_action('admin_init', 'shareyourcart_admin_init' );
add_action('init', 'shareyourcart_init');
add_action('admin_menu', 'shareyourcart_menu');
add_action('wp_ajax_nopriv_shareyourcart_get_settings', 'shareyourcart_get_settings' );
add_action('wp_ajax_shareyourcart_get_settings', 'shareyourcart_get_settings' );

add_action('wp_ajax_nopriv_shareyourcart_call_recovery_api','shareyourcart_call_recovery_api');
add_action('wp_ajax_shareyourcart_call_recovery_api','shareyourcart_call_recovery_api');

add_action('wp_head', 'shareyourcart_wp_head');

//add a menu page
function shareyourcart_menu()
{
    global $plugin_path;

    wp_register_style('shareyourcart-style', plugins_url('/style.css',__FILE__));

    $page = add_menu_page(
                            __('Share your cart settings'),
                            __('ShareYourCart'),
                            1, basename(__FILE__),
                            'shareyourcart_options',
                            $plugin_path.'shareyourcart.png'
            );
    add_action('admin_print_styles-'.$page,'shareyourcart_admin_styles' );
    
    $page = add_submenu_page(
                            basename(__FILE__),
                            __('Documentation'),
                            __('Documentation'),
                            1,
                            'shareyourcart_shortcodes_page',
                            'shareyourcart_shortcodes_page'
            );   
    add_action('admin_print_styles-'.$page,'shareyourcart_admin_styles' );  
	
	if(!shareyourcart_is_supported_cart_active())
	{
		//Add the meta options to supported
		add_meta_box( 'shareyourcart_metabox', 'ShareYourCart', 'shareyourcart_metabox', 'post', 'normal', 'high' );
		add_meta_box( 'shareyourcart_metabox', 'ShareYourCart', 'shareyourcart_metabox', 'page', 'normal', 'high' );
		add_action( 'save_post', 'shareyourcart_save_post' );
	}
}

function shareyourcart_admin_init()
{    
    wp_register_script('shareyourcart-script', plugins_url('/shareyourcart-script.js', __FILE__) );    
}

function shareyourcart_admin_styles()
{                        
    wp_enqueue_style('shareyourcart-style');            
    wp_enqueue_script('shareyourcart-script',plugins_url('/shareyourcart-script.js', __FILE__) , array( 'jquery' ),'',1);
    wp_localize_script('shareyourcart-script','MyAjax',array('ajaxurl' => admin_url('admin-ajax.php')));
}

//display the plug-in options page
function shareyourcart_options()
{
    global $title;
    global $wpdb;
    global $activation_status;
    global $plugin_path;         
	global $shareyourcart_secret_key;	

    $account_status = get_option("_shareyourcart_account_status"); 

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {       
        //if visual settings are submitted
        if($_POST['visual-form'] == 'visual-form')
        {                
            $button_skin = $_POST['button_skin'];             
            $button_position = $_POST['button_position'];
			$hide_on_checkout = empty($_POST['show_on_checkout']);
			$hide_on_product = empty($_POST['show_on_product']);
            
            //set the button skin
            update_option('_shareyourcart_button_skin',$button_skin);
            
            //set the button position
            update_option('_shareyourcart_button_position',$button_position); 

			//set the show
			update_option('_shareyourcart_hide_on_product',$hide_on_product);
				
			//set the show'
			update_option('_shareyourcart_hide_on_checkout',$hide_on_checkout);
				
            $status_message = '<div class="updated settings-error"><p><strong>Button settings successfully updated.</strong></p></div>';
        }
        //if account settings are submitted
        elseif($_POST['account-form'] == 'account-form')
        {
            $update_row=Array(
                'app_key' => $_POST['app_key'],
                'client_id' => $_POST['client_id'],
            );

            $wpdb->update($wpdb->base_prefix.'shareyourcart_settings',$update_row,array('id' =>1));
			
			//it is vital that we call the activation API here, to make sure, that the account is ACTIVE
			//call the account status function
			$message = '';
			$result = shareyourcart_setAccountStatusAPI($shareyourcart_secret_key, $_POST['client_id'], $_POST['app_key'], TRUE, $message);

			//TODO: investigate why do we need the update_option
			if($result == TRUE)  
			{
				$status_message = '<div class="updated settings-error"><p><strong>Account settings successfully saved.</strong></p>';
           
				update_option("_shareyourcart_account_status", 'active');
			}
			else
			{
				update_option("_shareyourcart_account_status", 'inactive');                
				$status_message = '<div class="updated settings-error"><p><strong>'.$message.'</strong></p>';
			}
			
			$status_message .= '</div>';
        }

    }

    $settings = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
    $current_skin = get_option('_shareyourcart_button_skin');   
    $current_position = get_option('_shareyourcart_button_position');
	$show_on_checkout = !get_option('_shareyourcart_hide_on_checkout');
	$show_on_product = !get_option('_shareyourcart_hide_on_product');
    include('views/shareyourcart-options.php');
}

/*****
*
* The box that appears on a page/post to allow the admin to enter post details
*
*************/
function shareyourcart_metabox($post)
{
	global $plugin_path;
	
	$price = get_post_meta( $post->ID , 'syc_price', true );
	$description = get_post_meta( $post->ID, 'syc_description', true);

	include('views/post-meta.php');
}

/*****
*
* The function called when a post is saved
*
***********/
function shareyourcart_save_post($post_id)
{ 
	global $plugin_path;
	
	// verify this came from the our screen and with proper authorisation,
	// because save_post can be triggered at other times		
	if ( !isset( $_POST['syc_nonce'] ) || !wp_verify_nonce( $_POST['syc_nonce'], $plugin_path )) {
		return;
	}
	
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	//save the price
	$price = isset( $_POST['syc_price'] ) ? trim( $_POST['syc_price'] ) : '';
	if ( $price != '' ) {
		update_post_meta( $post_id, 'syc_price', $price );
	} else {
		delete_post_meta( $post_id, 'syc_price' );
	}
	
	//save the description
	$description = isset( $_POST['syc_description'] ) ? trim( $_POST['syc_description'] ) : '';
	if ( $description != '' ) {
		update_post_meta( $post_id, 'syc_description', $description );
	} else {
		delete_post_meta( $post_id, 'syc_description' );
	}
}

function shareyourcart_shortcodes_page()
{
    global $plugin_path;  
	
	$action_url = admin_url()."admin-ajax.php?action=";
	
	if(shareyourcart_wp_e_commerce_is_active())
		$action_url.='shareyourcart_wp_e_commerce';
	else if (shareyourcart_eShop_is_active())
		$action_url.='shareyourcart_eshop';
        else if (shareyourcart_eStore_is_active())
                $action_url .= 'shareyourcart_estore';
	else
		$action_url = null;
	
    include('views/shortcodes.php');
    
}

//function called at the plug-in activation
function shareyourcart_activate() 
{
    global $wpdb, $shareyourcart_db_version, $shareyourcart_secret_key, $activation_status;

    //get the plug-in version
    $installed_ver = get_option( "_shareyourcart_db_version" );  

    $domain = get_bloginfo('url');
    $email = get_settings('admin_email');   

    //set your table names here
    $settings_table = $wpdb->base_prefix.'shareyourcart_settings';
    $tokens_table = $wpdb->base_prefix.'shareyourcart_tokens';
    $coupons_table = $wpdb->base_prefix.'shareyourcart_coupons';

    //if the plug-in is already installed
    if($installed_ver == $shareyourcart_db_version)
    {                     
        //get the app_key and client_id
        $settings = $wpdb->get_row("SELECT app_key, client_id FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");    

        //call the account status function
        $result = shareyourcart_setAccountStatusAPI($shareyourcart_secret_key, $settings->client_id, $settings->app_key, TRUE);

        if($result == TRUE)       
            update_option("_shareyourcart_account_status", 'active');
        else
            update_option("_shareyourcart_account_status", 'inactive');                
    }
    
    //if it's a fresh install
    else
    {   
        add_option("_shareyourcart_db_version", $shareyourcart_db_version); 
        add_option("_shareyourcart_account_status", 'inactive');
        add_option('_shareyourcart_button_skin','orange','','yes');
        add_option('_shareyourcart_button_position','normal','','yes');
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE " . $settings_table . " (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    app_key varchar(255),
                    client_id varchar(255),
                    PRIMARY KEY id (id));";    
        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . $tokens_table . " (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    token varchar(255),
                    session_id varchar(255),
                    PRIMARY KEY id (id));";
        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . $coupons_table . " (
                      id int(11) NOT NULL AUTO_INCREMENT,
                      token varchar(255),
                      coupon_id varchar(255),
                      PRIMARY KEY id (id));";    
        dbDelta($sql);

        $wpdb->insert($settings_table,Array('app_key'=>'0','client_id'=>'0'));                      

        //call the API to get the app_key & client_id
        $settings = shareyourcart_registerAPI($shareyourcart_secret_key, $domain, $email);        

        if($settings != FALSE)
        {                            
            update_option("_shareyourcart_account_status", 'active');

            //update the settings from the API
            $wpdb->update($settings_table,$settings,Array('id'=>1));
        }
        else
        {   
            update_option("_shareyourcart_account_status", 'inactive'); 
        }
    }   
}

//function that runs when the plug-in is deactivated
function shareyourcart_deactivate()
{
    global $wpdb, $shareyourcart_secret_key;        

    //get the app_key and client_id
    $settings = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
    
    shareyourcart_setAccountStatusAPI($shareyourcart_secret_key, $settings->client_id, $settings->app_key, FALSE);
    
    update_option('_shareyourcart_account_status','inactive');
}

//TODO: comment this
function add_shareyourcart_style() {
    global $plugin_path;
    $style_file = $plugin_path.'style.css';

    if (file_exists($style_file)) {
        wp_register_style('shareyourcart_stylesheet', $style_file);
        wp_enqueue_style('shareyourcart_stylesheet');
    }
}

function shareyourcart_get_settings()
{
    global $wpdb;
    $settings = $wpdb->get_row("SELECT app_key, client_id FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
    echo json_encode($settings);
    exit;
}

include(dirname(__FILE__).'/wp-e-commerce.php');
include(dirname(__FILE__).'/eshop.php');
include(dirname(__FILE__).'/estore.php');

/**
*
*  Authenticate with ShareYourCart.com
*
*/
function shareyourcart_auth($params)
{
        global $wpdb;

        //TODO: get the app key, client id ( from the database )
        $settings = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");

        //TODO: make sure the params contain the required entries
        if(!isset($params['app_key']))
        $params['app_key'] = $settings->app_key;

        if(!isset($params['client_id']))
        $params['client_id'] = $settings->client_id;

        $data = shareyourcart_startSessionAPI($params);

        //TODO: save the returned data, for later reference.
        //token and the session_id
        $wpdb->insert($wpdb->base_prefix."shareyourcart_tokens",$data);
}

/**
*
*  Validate the received coupon with ShareYourCart.com
*  Stops the php execution if the coupon is not valid
* 
*/
function shareyourcart_ensureValidCoupon()
{
        GLOBAL $wpdb;

        //call the core SDK function
        shareyourcart_ensureCouponIsValidAPI();

        //TODO: get session_id from database, according to the received TOKEN
        $tokens = $wpdb->get_row("SELECT session_id FROM ".$wpdb->base_prefix."shareyourcart_tokens WHERE token='".$_POST['token']."'");

        //check if count($tokens) == 1
        if($tokens==null)
        {
                header("HTTP/1.0 403");
                echo "Token not found";
                exit;
        }	

        //recreate the session of the user for which this coupon has been created
        $session_id = $tokens->session_id;

        session_destroy();
        session_id($session_id);
        session_start();
}

function shareyourcart_call_recovery_api()
{   
    global $shareyourcart_secret_key;
    
    $domain = get_bloginfo('url');    
    
    $new_email = $_POST['new_email'];
    $email = $new_email == '' ? get_settings('admin_email') : $new_email;
    
    
    // response output
    header( "Content-Type: application/json" );

    // It will test to see if the recovery option works
    
    
    if(($recover = shareyourcart_recoverAPI($shareyourcart_secret_key, $domain, $email)) == TRUE)
    {        
        echo json_encode('An email has been sent with your credentials.');
        exit;
    }    
    // It will test to see if the register option will work
    elseif(($register = shareyourcart_registerAPI($shareyourcart_secret_key, $domain, $email)) != FALSE)
    {               
        global $wpdb;

        $settings_table = $wpdb->base_prefix.'shareyourcart_settings';
        $wpdb->update($settings_table,$register,Array('id'=>1));
        
        echo json_encode('The account has been registered.');
        exit;
    }    
    // It will inform the user that the domain is already registered, and that recovery failed, and will present them with an opportunity to retry using a different email address.
    if($recover == FALSE && $register == FALSE )
    {
        $html = '<br/>
                 <form method="POST">
                 <label for="new-email">Other e-mail:</label><input type="text" name="new-email" id="new-email"/>
                 <button id="account_retry">Try again</button>
                 </form>';
        echo json_encode('This domain is already registered, and we FAILED to send ShareYourCart.com credentials to '.$email.'. Did you sign up with another e-mail address? If so, enter it here:'.$html);        
    }            
    exit;
}

//shortcode function
function shareyourcart_button()
{
	$button = "";

	if(shareyourcart_wp_e_commerce_is_active())
		$button=shareyourcart_wp_e_commerce_getButton();
	else if (shareyourcart_eShop_is_active())
		$button=shareyourcart_eshop_getButton();
        else if (shareyourcart_estore_is_active())
		$button=shareyourcart_estore_getButton();
	else
	{
		//render the generic button ( without a callback )
		ob_start(); 
		include(dirname(__FILE__).'/views/button.php');
		$button = ob_get_clean();
	}
		
    return $button;   
}

add_shortcode('shareyourcart','shareyourcart_button');
add_shortcode('shareyourcart_button','shareyourcart_button');

function shareyourcart_init()
{
	global  $SHAREYOURCART_API;
	wp_enqueue_script('shareyourcart_js_sdk', $SHAREYOURCART_API.'/js/button.js',array('jquery'));
        
}

//add any elements required to the head area
function shareyourcart_wp_head()
{
	global $wpdb, $post, $plugin_path;
	
	//get the  client id ( from the database )
        $settings = $wpdb->get_row("SELECT client_id FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
		
	echo "<meta property=\"syc:client_id\" content=\"$settings->client_id\" />\n";
	
	//if this is a single page, get the data from wordpress
	if(!shareyourcart_is_supported_cart_active() &&	(is_single() || is_page())) {
		
		//get the page/post title
		$title = the_title('', '', false);
		if(version_compare(phpversion(),'5.0.0','>=')){
			$title = html_entity_decode($title,ENT_QUOTES,'UTF-8');
		} else {
			$title = html_entity_decode($title,ENT_QUOTES);
		}
		
		//write the meta properties
		echo '<meta property="og:title" content="'.htmlspecialchars($title).'" />'."\n";
    	echo '<meta property="og:url" content="'.get_permalink().'" />'."\n";
		
		//show the description
		$description = get_post_meta( $post->ID, 'syc_description', true);
		if(empty($description)) $description = trim(get_the_excerpt());
		if(!empty($description)){
		    echo '<meta property="og:description" content="'.htmlspecialchars($description).'" />'."\n";
		}
		
		//if this post has a thumbnail, use it
		if(has_post_thumbnail()){
			$image = wp_get_attachment_image_src(get_post_thumbnail_id());
			echo '<meta property="og:image" content="'.$image[0].'" />'."\n";
		}
		
		//show the price
		$price = get_post_meta( $post->ID , 'syc_price', true );
		if(!empty($price)){
			echo '<meta property="syc:price" content="'.htmlspecialchars($price).'" />'."\n";
		}
	}
        
        $style_file = $plugin_path . 'style.css';
        $ie_style_file = $plugin_path . 'ie.css';
        
        echo '  <link rel="stylesheet" href="'.$style_file.'" type="text/css"/>
                <!--[if lt IE 9]>
                <link rel="stylesheet" href="'.$ie_style_file.'" type="text/css"/>
                <![endif]-->';
        
}

/***
*
* Returns TRUE if there is a supported cart active
*
*******/
function shareyourcart_is_supported_cart_active()
{
	return shareyourcart_wp_e_commerce_is_active() || shareyourcart_eShop_is_active() || shareyourcart_estore_is_active();
}

/**
	*
	* Ident in HTML. Convert leading spaces to &nbsp;
	*
	**/
function htmlIndent($src)
{
	//replace all leading spaces with &nbsp; 
	//Attention: this will render wrong html if you split a tag on more lines!
	return preg_replace_callback('/(^|\n)( +)/', create_function('$match',
		
		'return str_repeat("&nbsp;", strlen($match[0]));'
		
	), $src);
}



function rel2abs($rel, $base)
{
    /* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
   
    /* queries and anchors */
    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;
   
    /* parse base URL and convert to local variables:
       $scheme, $host, $path */
    extract(parse_url($base));
 
    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);
 
    /* destroy path if relative url points to root */
    if ($rel[0] == '/') $path = '';
   
    /* dirty absolute URL */
    $abs = "$host$path/$rel";
 
    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
   
    /* absolute URL is ready! */
    return $scheme.'://'.$abs;
}