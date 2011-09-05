<div class="wrap">
    <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
        <img src="<?php echo $plugin_path.'shareyourcart-logo.png'; ?>"/>
    </a>
    
    
    <div id="content">
        <h2>Standard Button</h2>
        <p>In order to see the 
            <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a>
            button on a <strong>post</strong> or <strong>page</strong> you can use the following shortcode:
        </p>
        <code>[shareyourcart]</code>
        
        <p>If you want to use the 
            <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a>
            button directly in a <strong>theme</strong> or <strong>page template</strong> you have to use the following function call:            
        </p>
        <code>echo do_shortcode('[shareyourcart]');</code>
		<h3>Remarks</h3>
		<ol>
	
<?php if (!(isset($action_url) && !empty($action_url))): //if no shopping cart is active ?>
		<li><p>For the button to work, you need to specify the following properties in the meta description area</p>
<code><?php echo htmlIndent(nl2br(htmlspecialchars('<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:og="http://ogp.me/ns#"
      xmlns:syc="http://www.shareyourcart.com">
  <head>
      <meta property="og:image" content="http://www.example.com/product-image.jpg"/>
      <meta property="syc:price" content="$10" />
      <meta property="og:description"
          content="
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
            Proin feugiat nunc quis nibh congue luctus. 
            Maecenas ac est nec turpis fermentum imperdiet.
          "/>
    ...
  </head>
  ...
</html>
'))); ?></code></li>
<?php endif; ?>

		<li><p>To position the  <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button, you need to override the following CSS classes</p>
		<ul>
			<li><code>button_iframe-normal</code> for the horrizontal button</li>
			<li><code>button_iframe</code> for the vertical button</li>
		</ul></li>
		</ol>
		
		
		<h2>Custom Button</h2>
		<p>If you want to fully style the  <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button, use instead the following HTML code</p>

<?php $custom_button = '<button class="shareyourcart-button" syc:layout="custom"';
if (isset($action_url) && !empty($action_url)){	
	//if there is no action url, it means none of the supported shopping carts are active,
	//so there would be no need for the callback attribute
	$custom_button .= ' syc:callback_url="'.$action_url.'" ';
 }
 
 $custom_button .= '>
     Get a <div class="shareyourcart-discount" ></div> discount
</button>'; ?>
		<code><?php echo  htmlIndent(nl2br(htmlspecialchars($custom_button))); ?></code>
		
<?php if (isset($action_url) && !empty($action_url)): //only show if a known shopping cart is active ?>
		<h3>Remarks</h3>
		<p>If you want to use the <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button on a product's page, you need to <strong>append</strong> <code>&p=&lt;product_id&gt;</code> to the <strong>syc:callback_url</strong> value, where <code>&lt;product_id&gt;</code> is the product's id</p>
<?php endif; ?>

	</div>
</div>