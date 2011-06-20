<?php
/*
 * 
Plugin Name: ShareYourCart
Plugin URI: http://www.shareyourcart.com
Description: ShareYourCart™ helps you get more customers by motivating satisfied customers to talk with their friends about your products.
Version: 1.2.1
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

$plugin_path = get_bloginfo('wpurl') . '/wp-content/plugins/shareyourcart/';

$activation_status = FALSE;
$shareyourcart_db_version = "1.0";
$shareyourcart_secret_key = 'f03f6ef2-9286-11e0-b1d5-005056867abd';

$VERTICAL_BUTTON_NORMAL = "vertical";
$VERTICAL_BUTTON_LEFT = "vertical-left";

//hook the actions
add_action('activate_shareyourcart/shareyourcart.php', 'shareyourcart_activate');
add_action('deactivate_shareyourcart/shareyourcart.php', 'shareyourcart_deactivate');
add_action('wp_print_styles', 'add_shareyourcart_style');
add_action('admin_init', 'shareyourcart_admin_init' );
add_action('admin_menu', 'shareyourcart_menu');
add_action('wp_ajax_nopriv_shareyourcart_get_settings', 'shareyourcart_get_settings' );
add_action('wp_ajax_shareyourcart_get_settings', 'shareyourcart_get_settings' );

add_action('wp_ajax_nopriv_shareyourcart_call_recovery_api','shareyourcart_call_recovery_api');
add_action('wp_ajax_shareyourcart_call_recovery_api','shareyourcart_call_recovery_api');


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
                            __('Shortcodes'),
                            __('Shortcodes'),
                            1,
                            'shareyourcart_shortcodes_page',
                            'shareyourcart_shortcodes_page'
            );   
    add_action('admin_print_styles-'.$page,'shareyourcart_admin_styles' );   
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
            
            //set the button skin
            if(!get_option('_shareyourcart_button_skin'))
                add_option('_shareyourcart_button_skin',$button_skin,'','yes');
            else
                update_option('_shareyourcart_button_skin',$button_skin);
            
            //set the button position
            if(!get_option('_shareyourcart_button_position'))
                add_option('_shareyourcart_button_position',$button_position,'','yes');
            else
                update_option('_shareyourcart_button_position',$button_position);            

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
    include('views/shareyourcart-options.php');
}

function shareyourcart_shortcodes_page()
{
    global $plugin_path;  
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
    if($installed_ver == "1.0")
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
    $style_file = WP_PLUGIN_URL . '/wp-content/plugins/shareyourcart/style.css';

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
		
    return $button;   
}
add_shortcode('shareyourcart','shareyourcart_button');
add_shortcode('shareyourcart_button','shareyourcart_button');