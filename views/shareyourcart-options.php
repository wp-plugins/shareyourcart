<?php global $SHAREYOURCART_CONFIGURE; ?>
<div class="wrap">
    <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
        <img src="<?php echo $plugin_path.'shareyourcart-logo.png'; ?>"/>
    </a>

    
    <?php echo $status_message; ?>
    <p><a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> helps you get more customers by motivating satisfied customers to talk with their friends about your products. Each customer that promotes your products, via social media, will receive a coupon that they can apply to their shopping cart in order to get a small discount.</p>
    
    <br />
    <div id="acount-options">    
        <form method="POST">
        <table class="form-table" name="shareyourcart_settings">
            <tr>
                <th scope="row">Client id</th>
                <td><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo $settings->client_id; ?>"/></td>
            </tr>
            <tr>
                <th scope="row">App key</th>
                <td><input type="text" name="app_key" id="app_key" class="regular-text" value="<?php echo $settings->app_key; ?>"/></td>
            </tr>
            <tr>
                <td></td>
                <td>These credentials are used to communicate with ShareYourCart&trade;. <a href="" title="<?php echo $account_status == 'active' ? 'Lost them?' : 'Get yours now!'; ?>" id="account-recovery"><?php echo $account_status == 'active' ? 'Lost them?' : 'Get yours now!'; ?></a></td>
            </tr>
        </table>
        <div class="submit"><input type="submit" value="Save"></div>       
        <input type="hidden" name="account-form" value="account-form"/>
        </form>
    </div>
    
    <div id="visual-options">
        <form method="POST">
            <table class="form-table" name="shareyourcart_settings">
                <tr>
                    <th scope="row">Button skin</th>
                    <td>
                        <select name="button_skin" id="button_skin">
                            <option name="orange" <?php echo $current_skin == 'orange' ? 'selected="selected"' : ''; ?> value="orange">Orange</option>
                            <option name="blue" <?php echo $current_skin == 'blue' ? 'selected="selected"' : ''; ?> value="blue">Blue</option>
                        </select>                        
                    </td>
                </tr>
                <tr>
                    <th scope="row">Button position</th>
                    <td>
                        <select name="button_position" id="button_position">
                            <option name="normal" <?php echo $current_position == 'normal' ? 'selected="selected"' : ''; ?> value="normal">Normal</option>
                            <option name="floating" <?php echo $current_position == 'floating' ? 'selected="selected"' : ''; ?> value="floating">Floating</option>
                        </select>                        
                    </td>
                </tr>
				<tr>
                    <th scope="row">Show by default on</th>
                    <td>
                            <input name="show_on_product" <?php echo $show_on_product ? 'checked="checked"' : ''; ?>  type='checkbox'>Product page</input>
                            <input name="show_on_checkout" <?php echo $show_on_checkout ? 'checked="checked"' : ''; ?> type='checkbox'>Checkout page</input>                        
                    </td>
                </tr>
            </table>
            <div class="submit"><input type="submit" value="Save"></div> 
            <input type="hidden" name="visual-form" value="visual-form"/>
        </form>
    </div>
    <br/><br/>
    <p>You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.</p>
    <form action="<?php echo $SHAREYOURCART_CONFIGURE; ?>" method="POST" id="configure-form" target="_blank">
        <div class="configure-button-container">
            <a href="#" id="configure-button" title="Configure" class="shareyourcart-button-orange">Configure</a>
            <input type="hidden" name="app_key" value="<?php echo $settings->app_key; ?>" />
            <input type="hidden" name="client_id" value="<?php echo $settings->client_id; ?>" />
        </div>
    </form>
</div>