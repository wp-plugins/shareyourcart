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
		<p>To position the  <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button, you need to override the following classes</p>
		<ul>
			<li><code>button_iframe-normal</code> for the horrizontal button</li>
			<li><code>button_iframe</code> for the vertical button</li>
		</ul>
		
		<h2>Custom Button</h2>
		<p>If you want to fully style the  <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button, you can simply use the following code</p>
		<code>&lt;button class="shareyourcart-button" shareyourcart:url="<?php echo $action_url; ?>"&gt;&gt;Get a &lt;div class="shareyourcart-discount" &gt;&lt;/div&gt; discount&lt;/button>"</code>
		<h3>Remarks</h3>
		<p>If you want to use the  <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;">ShareYourCart&trade;</a> button on a product's page, the only thing you need to do is <strong>append</strong> <code>&p=&lt;product_id&gt;</code> to the <strong>shareyourcart:url</strong>, where <code>&lt;product_id&gt;</code> is the product's id</p>
	</div>
</div>