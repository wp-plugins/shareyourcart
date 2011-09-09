<?php global $SHAREYOURCART_CONFIGURE; ?>
<div class="wrap">
    <!--<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
        <img src="<?php echo $plugin_path.'shareyourcart-logo.png'; ?>" style="float:right; width:260px;"/>
    </a>-->

    <p>Here you can enter the details that will be used by <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a></p>
    
    <br />
   
        <table>
            <tr>
                <th valign="top" scope="row" style="padding-top:6px;"><label for="syc_price">Product Price:</label></th>
                <td><input type="text" name="syc_price" id="syc_price" class="regular-text" style="width:450px;" value="<?php echo $price; ?>"/><br />
				<p class="howto">Include the currency sign as well. I.e: $10</p></td>
            </tr>
            <tr>
                <th valign="top" scope="row"><label for="syc_description">Description:</label></th>
                <td><textarea name="syc_description" id="syc_description" class="regular-text" style="width:450px; height:200px;"><?php echo $description; ?></textarea>
				<p class="howto">Leave blank to use the main description</p></td>
            </tr>
            <tr>
                <th valign="middle" scope="row">Product Image:</th>
                <td valign="middle"><p class="howto">Use wordpress' featured image functionality. You can find it on the right side.</p></td>
            </tr>
        </table>      
	<input type="hidden" name="syc_nonce" id="syc_nonce" value="<?php echo wp_create_nonce( $plugin_path ); ?>" />		
</div>