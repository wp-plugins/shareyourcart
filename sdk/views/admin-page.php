<div class="wrap">
<?php if($show_header):?>
    <h2>
        <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
            <img src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/shareyourcart-logo.png'); ?>"/>
        </a>
		<div class="syc-slogan">Increase your social media exposure by 10%!</div>
		
		<?php
			if(isset($this->adminFix)) echo "<br /><br /><br /><br /><br />";
			else echo "<br class=\"clr\" /> ";
		?>
    </h2>
	<?php endif; ?>

    <?php if(!empty($status_message)): ?>
	<div class="updated settings-error"><p><strong>
		<?php echo $status_message; ?>
	</strong></p></div>
	<?php endif; ?>
	
    <p><a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> helps you get more customers by motivating satisfied customers to talk with their friends about your products. Each customer that promotes your products, via social media, will receive a coupon that they can apply to their shopping cart in order to get a small discount.</p>
    
    <br />
    <div id="acount-options">      	
        <form method="POST" name="account-form">       
		<fieldset>	   		
		<div class="api-status" align="right">
                    API Status: 
                    <?php if($this->isActive()) : ?>
                        <span class="green">Enabled</span>
                    <?php else :?>
                        <span class="red">Disabled</span>
                    <?php endif;?>
                        <br />
                    <?php if($this->isActive()) : ?>
                        <input type="submit" value="Disable" name="disable-API" class="api-button" />
                    <?php else :?>
                        <input type="submit" value="Enable" name="enable-API" class="api-button" />
                    <?php endif;?>
                </div>                
        <table class="form-table-api" name="shareyourcart_settings">
            <tr>
                <th scope="row">Client ID</th>
                <td><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo $this->getClientId(); ?>"/></td>
            </tr>
            <tr>
                <th scope="row">App Key</th>
                <td><input type="text" name="app_key" id="app_key" class="regular-text" value="<?php echo $this->getAppKey(); ?>"/></td>
            </tr>
            <tr>
                <td></td>
                <td>These credentials are used to communicate with ShareYourCart&trade;. <a href="?<?php echo http_build_query(array_merge($_GET,array('syc-get-account'=>'true')),'','&')?>" id="account-recovery" class="api-link">Get yours now!</a></td>
            </tr>
        </table>
       <?php echo $html;?>
        <div class="submit"><input type="submit" name="syc-account-form" class="button" value="Save"></div>        
		</fieldset>			 
    </form>  
    </div>
    
    <br/>
    <fieldset>
    <p>You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.</p>
    <br />
 	 <form action="<?php echo $this->SHAREYOURCART_CONFIGURE; ?>" method="POST" id="configure-form" target="_blank">
       
        <div class="configure-button-container" align="center">
            <input type="submit" value="Configure" id="configure-button" class="shareyourcart-button-orange" />
            <input type="hidden" name="app_key" value="<?php echo $this->getAppKey(); ?>" />
            <input type="hidden" name="client_id" value="<?php echo $this->getClientId(); ?>" />
        </div>   
       
    </form>
    </fieldset>
        <br />
	<?php if($show_footer):?>	
	<h2>Contact</h2>
	<p>You can find help in our <a href="http://shareyourcart.uservoice.com" target="_blank" title="forum">forum</a>, or if you have a private question, you can <a href="http://www.shareyourcart.com/contact" target="_blank">contact us directly</a></p>
	<br />
	<?php endif; ?>
</div>