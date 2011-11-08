<div class="shareyourcart-button" 
<?php if(isset($callback_url) && !empty($callback_url)): ?>
data-syc-callback_url="<?php echo $callback_url; ?>"
<?php endif; ?> 
data-syc-layout="custom"
><img class="syc-button-img" src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/pixel.gif'); ?>" /></div>

<style>
	.syc-button-img {
		cursor:pointer;
		background:url('<?php echo $button_img;?>');
		width: <?php echo $button_img_width; ?>px;
		height: <?php echo $button_img_height; ?>px;
	}
	.syc-button-img:hover {
		background:url('<?php echo $button_img_hover; ?>');
		width: <?php echo $button_img_hover_width; ?>px;
		height: <?php echo $button_img_hover_height; ?>px;
	}
</style>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>