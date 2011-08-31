<?php

add_action('wp_ajax_nopriv_shareyourcart_eshop', 'shareyourcart_eshop');
add_action('wp_ajax_shareyourcart_eshop', 'shareyourcart_eshop');

add_action('wp_ajax_nopriv_shareyourcart_eshop_coupon', 'shareyourcart_eshop_coupon');
add_action('wp_ajax_shareyourcart_eshop_coupon', 'shareyourcart_eshop_coupon');

add_action( 'plugins_loaded', 'shareyourcart_eshop_init' );

/**
* 
* Called when all plugins have been initialized
* 
**/
function shareyourcart_eshop_init()
{
	/**** Only link if it is a compatible version *****/
	if(version_compare(ESHOP_VERSION,'6.2.8') >= 0)
	{
		remove_shortcode('eshop_show_checkout');
		add_shortcode('eshop_show_checkout', 'shareyourcart_eshop_show_checkout');

		remove_shortcode('eshop_show_cart');
		add_shortcode('eshop_show_cart', 'shareyourcart_eshop_show_cart');

		//remove_shortcode('eshop_details');
		//add_shortcode('eshop_details', 'shareyourcart_eshop_details');
	}
}

/**
*
* returns TRUE if the eShop plugin is active
*
**/
function shareyourcart_eShop_is_active()
{
	//check if wp-ecommerce is active
	if (!function_exists( 'is_plugin_active' ) )
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	
	return is_plugin_active( 'eshop/eshop.php' );
}

/**
*
* the function called when the users clicks on the share button
*
**/
function shareyourcart_eshop() {
	global  $blog_id, $eshopoptions, $post;
	
	//check if there is an eshop plugin
	if(!shareyourcart_eShop_is_active())
	exit;

	//setup the params
	$params = array(
	'callback_url' => get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=shareyourcart_eshop_coupon',
	);
	
	//in case there is a certain product we are reffering to, also add it to the callback url
	if(isset($_REQUEST['p']))
	{	
		$params['callback_url'] .= '&p='.$_REQUEST['p'];
	}
	
	
	$params['success_url'] = get_permalink($eshopoptions['cart']);
	$params['cancel_url'] = get_permalink($eshopoptions['cart']);
	
	$currsymbol = $eshopoptions['currency_symbol'];
	
	//there is no product set, thus send the products from the shopping cart
	if(!isset($_REQUEST['p']))
	{
		$eshopcartarray = $_SESSION['eshopcart'.$blog_id];
  
		foreach($eshopcartarray as $productid => $opt)
		{
			$post_id = $opt['postid'];
			
			//get the product data from the db
			$eshop_product=maybe_unserialize(get_post_meta($post_id, '_eshop_product','true'));
			$img = wp_get_attachment_image_src(get_post_thumbnail_id($post_id));
	
			$params['cart'][] = array(
			'item_name' => get_the_title($post_id),
			'item_description' => $eshop_product['description'],
			'item_url' => get_permalink($post_id),
			'item_price' => sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($opt['price'],__('2','eshop'))),
			'item_picture_url' =>  !empty($img) ? $img[0] : '',
			);
		}
	}
	else //there is a product specified, so send that one
	{  
		$the_query = new WP_Query( array(
		'post_type' => 'any',
		'p'  => $_REQUEST['p'],
		));
		$posts = $the_query->get_posts();  
		$post = $posts[0];
		setup_postdata($post);
		
		//get the product information from the db
		$eshop_product=maybe_unserialize(get_post_meta($post->ID, '_eshop_product','true'));
		$img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID));
		
		$params['cart'][] = array(
		'item_name' => get_the_title(),
		'item_url' => get_permalink(),
		'item_price' => sprintf( _c('%1$s%2$s|1-currency symbol 2-amount','eshop'), $currsymbol, number_format_i18n($eshop_product['products'][1]['price'],__('2','eshop'))),
		'item_description' => $eshop_product['description'],
		'item_picture_url' =>  !empty($img) ? $img[0] : '',
		);
	}
	
	shareyourcart_auth($params);
	exit;
}

/**
*
* The callback function
*
**/
function shareyourcart_eshop_coupon()
{	
	global $wpdb, $blog_id, $eshopcartarray,$eshopoptions, $shiparray, $post;

	//check if there is an eshop plugin
	if(!shareyourcart_eShop_is_active())
	{
		header("HTTP/1.0 403");
		exit;
	}
	
	//make sure the coupon received is a valid one
	shareyourcart_ensureValidCoupon();
	
	if(!isset($_REQUEST['p'])) //there is no product specified, so look at the shopping cart
	{
		$eshopcartarray = $_SESSION['eshopcart'.$blog_id];

		//calculate the total amount of the cart
		$total = 0;
		foreach($eshopcartarray as $productid => $opt)
		{
			$total += $opt['price'];
		}
	}
	else
	{
		//get the product
		$eshop_product=maybe_unserialize(get_post_meta($_REQUEST['p'], '_eshop_product','true'));
		
		//get it's price 
		$total = $eshop_product['products'][1]['price'];
	}
	
	
	/********** Insert coupon in database ******************/

	$coupon_code = $_POST['coupon_code'];
	$discount = $_POST['coupon_value'];

	//the coupon should be valid for 1 day
	$end_date = date('Y-m-d', strtotime("now +1 day"));  //TODO: this seems to work only for the new version of eShop
	
	switch($_POST['coupon_type'])
	{
	case 'amount': 
		{
			//since eShop does not support fixed amount coupons, convert it to a % one
			$discount = ($discount / ((float)$total));
			$eshop_code_type =1;    
			break;
		}
	case 'percent': $eshop_code_type = 1; break;
	case 'free_shipping' : 
		{
			$discount = 0; //put this 0 in order not to interfere with other calculus
			$eshop_code_type = 4; 
			break;
		}
	default : $eshop_code_type = 1;
	}
	
	$coupon_data = array(
	'dtype' => $eshop_code_type,
	'disccode' => $coupon_code,
	'percent' => $discount,
	'remain' => 1,
	'enddate' => $end_date,
	'live' => 'yes',
	);

	if($wpdb->insert($wpdb->prefix.'eshop_discount_codes',$coupon_data) === FALSE)
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
	exit;
}

/**
*
* Append the button to the checkout page
*
**/
function shareyourcart_eshop_show_checkout($atts, $content = ''){

	global  $blog_id;
	
	$output = '';
	
	//if there is at least an item in the cart
	if(!empty($_SESSION['eshopcart'.$blog_id])&&!get_option('_shareyourcart_hide_on_checkout'))
	{
		$output = shareyourcart_eshop_getButton();
	}
	
	return $output . eshop_show_checkout($atts, $content);
}

/**
*
* Append the button to the cart page
*
**/
function shareyourcart_eshop_show_cart($atts, $content = ''){
	global  $blog_id;
	
	$output = '';
	
	//if there is at least an item in the cart
	if(!empty($_SESSION['eshopcart'.$blog_id])&&!get_option('_shareyourcart_hide_on_checkout'))
	{
		$output = shareyourcart_eshop_getButton();
	}
	
	return  $output.eshop_show_cart($atts, $content);
}

/**
*
*   Show the details about a product
*
*/
/* this shortcode does not longer seem to be used 
function shareyourcart_eshop_details($atts, $content = '')
{
	global $post;
	return shareyourcart_eshop_getButton($post->ID).eshop_details($atts,$content);
}*/

/*
*
*   Render the eShop Button
*   
*/
function shareyourcart_eshop_getButton($product_id = null)
{
	global $wpdb, $post;
	
	//check if the product has not been set 
	if(!isset($product_id))
	{
		//and we are on the product page
		$eshop_product=maybe_unserialize(get_post_meta($post->ID, '_eshop_product','true'));	
		if(!empty($eshop_product))
		{
			//set the product id
			$product_id = $post->ID;
		}
	}
	
	//the callback url from WP E-Commerce that will be called once the button is pressed
	$callback_url = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=shareyourcart_eshop&'.(isset($product_id) ? 'p='.$product_id : null);
	
	//render the view
	ob_start(); 
	include(dirname(__FILE__).'/views/button.php');
	return ob_get_clean();
}