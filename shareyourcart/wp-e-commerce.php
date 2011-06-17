<?php

add_action('wp_ajax_nopriv_shareyourcart_wp_e_commerce', 'shareyourcart_wp_e_commerce');
add_action('wp_ajax_shareyourcart_wp_e_commerce', 'shareyourcart_wp_e_commerce');

add_action('wp_ajax_nopriv_shareyourcart_wp_e_commerce_coupon', 'shareyourcart_wp_e_commerce_coupon');
add_action('wp_ajax_shareyourcart_wp_e_commerce_coupon', 'shareyourcart_wp_e_commerce_coupon');

add_action( 'plugins_loaded', 'shareyourcart_wp_e_commerce_init' );

/**
* 
* Called when all plugins have been initialized
* 
**/
function shareyourcart_wp_e_commerce_init()
{
	/**** CHECKOUT PAGE *****/
	if(version_compare(WPSC_VERSION,'3.8') >= 0)
		add_action('wpsc_before_shipping_of_shopping_cart', 'shareyourcart_wp_e_commerce_button_shortcode');  //wp e-commerce v3.8+
	else
		add_action('wpsc_before_form_of_shopping_cart', 'shareyourcart_wp_e_commerce_button_shortcode'); //wp e-commerce v3.7

	/**** PRODUCT PAGE ******/
	add_action('wpsc_top_of_products_page', 'shareyourcart_wp_e_commerce_products_page'); //wp e-commerce v3.7+
}

/**
*
* returns TRUE if the WP e-Commerce plugin is active
*
**/
function shareyourcart_wp_e_commerce_is_active()
{
	//check if wp-ecommerce is active
	if (!function_exists( 'is_plugin_active' ) )
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	
	return is_plugin_active( 'wp-e-commerce/wp-shopping-cart.php' );
}

/**
*
* the function called when the users clicks on the share button
*
**/
function shareyourcart_wp_e_commerce() {

	if(!shareyourcart_wp_e_commerce_is_active())
	exit;

	//specify the parameters
	$params = array(
	'callback_url' => get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=shareyourcart_wp_e_commerce_coupon',
	'success_url' => get_option('shopping_cart_url'),
	'cancel_url' => get_option('shopping_cart_url'),
	);

	//there is no product set, thus send the products from the shopping cart
	if(!isset($_REQUEST['p']))
	{	
		//add the cart items to the arguments
		while (wpsc_have_cart_items()) : wpsc_the_cart_item();

		//load the product from db, in order to obtain the description / picture	
		if(version_compare(WPSC_VERSION,'3.8') >= 0)
		{	
			global $wp_query;
			$wp_query = new WP_Query(array(
			'post_type' => 'wpsc-product',
			'p'  => wpsc_cart_item_product_id(),
			)); 
		}
		elseif (version_compare(WPSC_VERSION,'3.7') >= 0)
		{			
			global $wpsc_query;
			$wpsc_query = new WPSC_Query(array(
			'product_id'  => wpsc_cart_item_product_id(),
			)); 
		}
		
		while (wpsc_have_products()) : wpsc_the_product();
		
		$params['cart'][] = array(
		"item_name" => wpsc_cart_item_name(),
		"item_url" => wpsc_cart_item_url(),
		"item_price" => function_exists('wpsc_cart_single_item_price') ? wpsc_cart_single_item_price() : wpsc_cart_item_price(),
		"item_description" => wpsc_the_product_description(),
		"item_picture_url" => wpsc_the_product_image(),
		);
		
		endwhile; //wpsc_query loop
		
		endwhile; //cart loop
	}
	else
	{
		if(version_compare(WPSC_VERSION,'3.8') >= 0)
		{	
			//get the details of the specified product
			global $wp_query;
			$wp_query = new WP_Query(array(
			'post_type' => 'wpsc-product',
			'p'  => $_REQUEST['p'],
			)); 
		}
		else if (version_compare(WPSC_VERSION, '3.7') >= 0)
		{			
			//get the details of the specified product
			global $wpsc_query;
			$wpsc_query = new WPSC_Query(array(
			'product_id'  => $_REQUEST['p'],
			)); 
		}
		
		while (wpsc_have_products()) : wpsc_the_product();
		$params['cart'][] = array(
		"item_name" => wpsc_the_product_title(),
		"item_description" => wpsc_the_product_description(),
		"item_url" => wpsc_the_product_permalink(),
		"item_price" => wpsc_the_product_price(), 
		"item_picture_url" => wpsc_the_product_image(),
		);
		endwhile;
	}

	shareyourcart_auth($params);
	exit;
}

function shareyourcart_wp_e_commerce_coupon(){
	global $wpdb, $SHAREYOURCART_API_VALIDATE;
	
	//check if wp-ecommerce is active
	if(!shareyourcart_wp_e_commerce_is_active())
	{
		header("HTTP/1.0 403");
		exit;
	}

	//make sure the coupon received is a valid one
	shareyourcart_ensureValidCoupon();
	
	//initialize the cart object from the loaded $_SESSION
	if(version_compare(WPSC_VERSION,'3.8')>=0)
	{
		wpsc_core_setup_cart();
	}
	else //v3.7
	{
		wpsc_initialisation();
	}

	/********** Insert coupon in database ******************/

	//link this 3 to the input params
	$coupon_code=$_POST['coupon_code'];
	$discount=$_POST['coupon_value'];

	switch($_POST['coupon_type'])
	{
	case 'amount': $discount_type = 0; break;
	case 'percent': $discount_type = 1; break;
	case 'free_shipping' : $discount_type = 2; break;
		default : $discount_type = 0;
	}

	$coupon_data = array(
	'coupon_code' => $coupon_code,
	'value' => $discount,
	'is-percentage' => $discount_type,
	'use-once' => 1,
	'is-used' => 0,
	'active' => 1,
	'every_product' => 0,
	'start' => date('Y-m-d', strtotime("now")),
	'expiry' => date('Y-m-d', strtotime("now +1 day")),
	'condition' => 'a:0:{}',
	);

	if($wpdb->insert(WPSC_TABLE_COUPON_CODES,$coupon_data) === FALSE)
	{
		//the save failed
		header("HTTP/1.0 403");
		exit;
	}

	//add the coupon id in shareyourcart coupons table
	$data=array(
	'token' => $_POST['token'],
	'coupon_id' => $wpdb->insert_id,
	);

	$wpdb->insert($wpdb->base_prefix."shareyourcart_coupons",$data);

	//check if the coupon is intended to be applied to the current cart
	if(empty($_POST['save_only']))
	{
		/*********** Apply to coupon to the cart ***************/
		//apply the coupon to the shopping cart
		$_POST['coupon_num'] = $coupon_code;
		wpsc_coupon_price();
	}

	exit;
}

//called for product / products pages
function shareyourcart_wp_e_commerce_products_page()
{ 
	//do not display the button if we are on the 
	//product list page
	if(wpsc_is_single_product())   //TODO: this is not valid here, because is outside of the loop
	{
		echo shareyourcart_wp_e_commerce_getButton();
	}
}

//the button shortcode
function shareyourcart_wp_e_commerce_button_shortcode()
{
	echo shareyourcart_wp_e_commerce_getButton();
}

/*
*
*   Render the eCommerce Button
*   
*/
function shareyourcart_wp_e_commerce_getButton($product_id = null)
{
	global $wpdb;
	
	//get the app key, client id and build the shopping cart url( from the settings table )
	$settings = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
	$client_id = $settings->client_id;
		
	//check if the product has not been set and we are on the product page
	if(!isset($product_id) && wpsc_is_single_product())
	{
		//set the product id
		$product_id = wpsc_the_product_id();
	}
	
	ob_start();
	
	//render the view 
        include(dirname(__FILE__).'/views/wp-e-commerce_view.php');
	
	return ob_get_clean();
}
?>