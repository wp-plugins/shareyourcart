<?php 
    global $SHAREYOURCART_API, $plugin_path;
    
    $style_file = $plugin_path . 'style.css';
    $ie_style_file = $plugin_path . 'ie.css';
    
    $button_skin = get_option('_shareyourcart_button_skin');
    $button_position = get_option('_shareyourcart_button_position');
    $use_iframe = $button_position == 'floating' ? TRUE : FALSE;
?>

<link rel="stylesheet" href="<?php echo $style_file; ?>" type="text/css"/>
<!--[if lt IE 9]>
<link rel="stylesheet" href="<?php echo $ie_style_file ; ?>" type="text/css"/>
<![endif]-->

<iframe src="<?php echo $SHAREYOURCART_API; ?>/button?client_id=<?php echo urlencode($client_id); ?>&callback_url=<?php echo urlencode(bloginfo('wpurl').'/wp-admin/admin-ajax.php?&action=shareyourcart_wp_e_commerce&'.(isset($product_id) ? 'p='.$product_id : null)) ?>&skin=<?php echo $button_skin;  ?>&orientation=<?php echo $button_position; ?>"<?php if($use_iframe) echo 'class="button_iframe"';else echo 'class="button_iframe-normal"';?>frameBorder="0" scrolling="no"></iframe> 