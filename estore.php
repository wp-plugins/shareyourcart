<?php

add_action('wp_ajax_nopriv_shareyourcart_estore', 'shareyourcart_estore');
add_action('wp_ajax_shareyourcart_estore', 'shareyourcart_estore');

add_action('wp_ajax_nopriv_shareyourcart_estore_coupon', 'shareyourcart_estore_coupon');
add_action('wp_ajax_shareyourcart_estore_coupon', 'shareyourcart_estore_coupon');

add_action( 'plugins_loaded', 'shareyourcart_estore_init' );

/**
* 
* Called when all plugins have been initialized
* 
**/
function shareyourcart_estore_init()
{
    
	/**** CHECKOUT PAGE *****/
        add_filter('the_content', 'shareyourcart_estore_button', 12);
        
}

/**
*
* returns TRUE if the eStore plugin is active
*
**/
function shareyourcart_estore_is_active()
{
	//check if wp-ecommerce is active
	if (!function_exists( 'is_plugin_active' ) )
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	
	return is_plugin_active( 'wp-cart-for-digital-products/wp_cart_for_digital_products.php' );
}

/**
*
* the function called when the users clicks on the share button
*
**/
function shareyourcart_estore() {

    global  $blog_id, $eshopoptions, $post, $wpdb;
    
	if(!shareyourcart_estore_is_active())
	exit;
        
	//specify the parameters
	$params = array(
		'callback_url' => get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=shareyourcart_estore_coupon',
		'success_url' => digi_cart_current_page_url(),
		'cancel_url' => digi_cart_current_page_url(),
	);
        
	//there is no product set, thus send the products from the shopping cart
	if(!isset($_REQUEST['p']))
	{	
                
		global $wp_query;
            
        // Query the database for all posts that contain the Product shortcode
        $sql  = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_content LIKE '%[wp_eStore:product_id:%' AND post_status = 'publish'";
        $results = $wpdb->get_results($sql);    
		$pattern = '#\[wp_eStore:product_id:.+:end]#';
		$old = false;
		$fancy = false;
		
		// Maybe it's fancy
		if(!$results) {
			// Query the database for all posts that contain the OLD product shortcode
       		$sql  = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_content LIKE '%[wp_eStore_fancy%' AND post_status = 'publish'";
        	$results = $wpdb->get_results($sql); 	
			$pattern = '#\[wp_eStore_fancy.+ id=.+]#';
			$fancy = true;
		}
		
		// Old shortcode
		if(!$results) {
			// Query the database for all posts that contain the OLD product shortcode
       		$sql  = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_content LIKE '%?wp_eStore_add_to_cart=%' AND post_status = 'publish'";
        	$results = $wpdb->get_results($sql); 	
			$pattern = '#\?wp_eStore_add_to_cart=.+"]#';
			$old = true;
			$fancy = false;
		}
          
		//add the cart items to the arguments
		foreach ($_SESSION['eStore_cart'] as $item) {
                       
                    // Iterates the products to find the one that we need
                    $j = 0;
                    for($j = 0; $j < sizeof($results); $j++) {

                        preg_match_all($pattern, $results[$j]->post_content, $matches);
     
	 					// If no matches are found continues the for without proceeding anymore
	 					if(!$matches[0][0]) continue;
						
                        foreach ($matches[0] as $match) {
                     		if(!$old) $pat = '[wp_eStore:product_id:';
							else $pat = '?wp_eStore_add_to_cart=';
							
							if($fancy) {
								$m = str_replace('[wp_eStore_fancy1 id=', '', $match);
								$m = str_replace('[wp_eStore_fancy2 id=', '', $m);	
							} else {
        	                    $m = str_replace ($pat, '', $match);
							}
		
                            if(!$old) $pat = ':end]';
							else $pat = '"]';
							
							if($fancy) $m = str_replace (']', '', $m);
                            else $m = str_replace ($pat, '', $m);

                            $pieces = explode('|',$m);
                            $key = $pieces[0];
                        }
                        
                        if($key == $item['item_number']) break;
						
                    }  
                    
                    // Get the image of the product
                    $img = wp_get_attachment_image_src(get_post_thumbnail_id($results[$j]->ID));       
                    
                    // Query the database to find the description of the product
                    $sql2  = "SELECT description FROM " . $wpdb->prefix . "wp_eStore_tbl WHERE id = " . $key;
                    $results2 = $wpdb->get_results($sql2);	

                    $params['cart'][] = array(
                    	"item_name" => $item['name'],
                    	"item_url" => $results[$j]->guid,
                    	"item_price" => print_digi_cart_payment_currency($item['price'], WP_ESTORE_CURRENCY_SYMBOL),
                    	"item_description" => $results2[0]->description, 
                    	"item_picture_url" => !empty($img) ? $img[0] : '',
                    );
		
                } //cart loop
	
	} else {
	
        //get the details of the specified product
		global $wp_query;
                
                $sql  = "SELECT * FROM " . $wpdb->prefix . "posts WHERE id = " . $_REQUEST['p'];
                $results = $wpdb->get_results($sql);	
				                
                $pattern = '#\[wp_eStore:product_id:.+:end]#';
				$old = false;
				$fancy = false;
                preg_match_all($pattern, $results[0]->post_content, $matches);
				
				// Maybe it's the fancy alternative
				if(!$matches[0][0]) {
					$pattern = '#\[wp_eStore_fancy.+ id=.+]#';
					$fancy = true;
    	            preg_match_all($pattern, $results[0]->post_content, $matches);	
				}
				
				// Maybe it's old shortcode
				if(!$matches[0][0]) {
					$pattern = '#\?wp_eStore_add_to_cart=.+"]#';
					$old = true;
					$fancy = false;
					preg_match_all($pattern, $results[0]->post_content, $matches);
				}
				
				if($matches[0][0]) {
					foreach ($matches[0] as $match) {
						if(!$old) $pattern = '[wp_eStore:product_id:';
						else $pattern = '?wp_eStore_add_to_cart=';
								
						if($fancy) {
							$m = str_replace('[wp_eStore_fancy1 id=', '', $match);
							$m = str_replace('[wp_eStore_fancy2 id=', '', $m);
						} else {
							$m = str_replace($pattern, '', $match);
						}
	
						if(!$old) $pattern = ':end]';
						else  $pattern = '"]';
						
						if($fancy) $m = str_replace (']', '', $m);
						else $m = str_replace ($pattern, '', $m);
	
						$pieces = explode('|',$m);
						$key = $pieces[0];
					}
				}

                // Query the database to find the details of the product
				$sql2  = "SELECT * FROM " . $wpdb->prefix . "wp_eStore_tbl WHERE id = " . $key;
                $results2 = $wpdb->get_results($sql2);	
                
                $img = wp_get_attachment_image_src(get_post_thumbnail_id($results[0]->ID));
                
                $params['cart'][] = array(
					'item_name' => $results2[0]->name,
					'item_url' => $results[0]->guid,
					'item_price' => print_digi_cart_payment_currency($results2[0]->price, WP_ESTORE_CURRENCY_SYMBOL),
					'item_description' => $results2[0]->description,
					'item_picture_url' =>  !empty($img) ? $img[0] : '',
				);                           
	
	}

	shareyourcart_auth($params);
	exit;
}

function shareyourcart_estore_coupon(){
    
	global $wpdb, $SHAREYOURCART_API_VALIDATE;
	
	//check if eStore is active
	if(!shareyourcart_estore_is_active())
	{
		header("HTTP/1.0 403");
		exit;
	}

	//make sure the coupon received is a valid one
	shareyourcart_ensureValidCoupon();

	/********** Insert coupon in database ******************/

	//link this 3 to the input params
	$coupon_code = $_POST['coupon_code'];
	$discount = $_POST['coupon_value'];

	switch($_POST['coupon_type'])
	{
            case 'amount': $discount_type = 1; break;
            case 'percent': $discount_type = 0; break;
            default : $discount_type = 0;
	}
        
        $post_coupon_active = 'Yes';

        $coupon_table_name = $wpdb->prefix . "wp_eStore_coupon_tbl";
        
        $sql = "INSERT INTO ".$coupon_table_name."(coupon_code, discount_value, discount_type, active, redemption_limit, redemption_count, property, logic, value, expiry_date) 
                     VALUES ('".$coupon_code."', '".$discount."', '".$discount_type."', '".$post_coupon_active."', 1, 0, 1, 1, 0, '".date('Y-m-d', strtotime('now +1 day'))."')";
	$results = $wpdb->query($sql);
        
	if(!$results)
	{
		//the save failed
		header("HTTP/1.0 403");
                echo "Couldn't insert coupon in the cart's database.";
		exit;
	}

	//add the coupon id in shareyourcart coupons table
	$data = array(
            'token' => $_POST['token'],
            'coupon_id' => $wpdb->insert_id,
	);

	$wpdb->insert($wpdb->base_prefix."shareyourcart_coupons", $data);

	//check if the coupon is intended to be applied to the current cart
	if(empty($_POST['save_only']))
	{
		/*********** Apply to coupon to the cart ***************/
		//apply the coupon to the shopping cart
		$_POST['coupon_code'] = $coupon_code;
                
		eStore_apply_discount($coupon_code);
	}

	exit;
}


function shareyourcart_estore_button($content) { 
 
 	global $shareyourcart_button_showed;
 
 	// If the button was already displayed return the content without adding the button again
 	if($shareyourcart_button_showed) return $content;
    
	// Checks to see if this is the Cart
    $check = strstr(strip_tags(get_the_content()), "[wp_eStore_cart");
    
	// if it's a single page
    if((shareyourcart_estore_is_single_product() && !get_option('_shareyourcart_hide_on_product'))) {
		
        $content .= shareyourcart_estore_getButton();
		
		// The button has been displayed
		$shareyourcart_button_showed = true;
		
	// if it's the cart
    } else if (!get_option('_shareyourcart_hide_on_checkout') && $check && digi_cart_not_empty()) {
        
		$content .= shareyourcart_estore_getButton();
		
		// The button has been displayed
		$shareyourcart_button_showed = true;
		
    }
    
    return $content;
    
}

/*
*
*   Render the eCommerce Button
*   
*/
function shareyourcart_estore_getButton($product_id = null)
{
	global $wp_query, $shareyourcart_button_showed;
	
	//check if the product has not been set and we are on the product page
	if(!isset($product_id) && shareyourcart_estore_is_single_product())
	{
		//set the product id
		if(get_option('_shareyourcart_hide_on_product')) return;
		$product_id = $wp_query->post->ID;
	} else {
		if(get_option('_shareyourcart_hide_on_checkout')) return;
	}

	//the callback url from WP E-Commerce that will be called once the button is pressed
	$callback_url = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=shareyourcart_estore&'.(isset($product_id) ? 'p='.$product_id : null);
	
	//render the view 
	ob_start();
	include(dirname(__FILE__).'/views/button.php');
	
	$shareyourcart_button_showed = true;
	return ob_get_clean();
}

// Function to check if we are on a products page
function shareyourcart_estore_is_single_product()
{
	
        global $wp_query;

        $pattern = '#\[wp_eStore:product_id:.+:end]#';
        preg_match_all($pattern, $wp_query->post->post_content, $matches);
		
		// If nothing is found try the fancy scheme for eStore
		if(!$matches[0][0]) {
			$pattern = '#\[wp_eStore_fancy.+ id=.+]#';
       		preg_match_all($pattern, $wp_query->post->post_content, $matches);	
		}

	        // If multiple products are displayed on one page break;
                if(count($matches[0]) > 1) return false;
		
		// If nothing is found try the old scheme for eStore with the old shortcode
		if(!$matches[0][0]) {

			$pattern = '#\wp_eStore_add_to_cart#';
			preg_match_all($pattern, $wp_query->post->post_content, $matches);
			
		}
        
		// If we found any match
		if($matches[0][0]) {
			
			// If the pattern is found verifies that we are not on a category listing page
			if(is_single() or is_page()) return true;
			
		}
       
	   	// Return false otherwise
        return false;
        
}

?>