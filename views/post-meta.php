<?php global $SHAREYOURCART_CONFIGURE; ?>
<div class="wrap">
    <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
        <img src="<?php echo $plugin_path.'shareyourcart-logo.png'; ?>"/>
    </a>

    <p>Here you can enter the details that will be used by <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a></p>
    
    <br />
   
        <table>
            <tr>
                <th scope="row"><label for="syc_price">Product Price:</label></th>
                <td><input type="text" name="syc_price" id="syc_price" class="regular-text" value="<?php echo $price; ?>"/><br />
				<p class="howto">Include the currency sign as well. I.e: $10</p></td>
            </tr>
            <tr>
                <th scope="row"><label for="syc_description">Product Description:</label></th>
                <td><textarea name="syc_description" id="syc_description" class="regular-text"><?php echo $description; ?></textarea>
				<p class="howto">Leave blank to use the main description</p></td>
            </tr>
            <tr>
                <th scope="row">Product Image:</th>
                <td><p class="howto">Use wordpress' featured image functionality. You can find it on the right side of this page.</p></td>
            </tr>
        </table>      
	<input type="hidden" name="syc_nonce" id="syc_nonce" value="<?php echo wp_create_nonce( $plugin_path ); ?>" />		
</div>