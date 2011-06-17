<?php 
// if ( !defined('WP_UNINSTALL_PLUGIN') ) {
// exit();
// }
    global $wpdb;
        
    //set your table names here
    $settings_table = $wpdb->base_prefix.'shareyourcart_settings';
    $tokens_table = $wpdb->base_prefix.'shareyourcart_tokens';
    $coupons_table = $wpdb->base_prefix.'shareyourcart_coupons';
        
    $wpdb->query('DROP TABLE '.$coupons_table);
    $wpdb->query('DROP TABLE '.$tokens_table);
    $wpdb->query('DROP TABLE '.$settings_table);
    
    //remove the options used
    delete_option('_shareyourcart_db_version');
    delete_option('_shareyourcart_account_status');
    delete_option('_shareyourcart_button_skin');
?>