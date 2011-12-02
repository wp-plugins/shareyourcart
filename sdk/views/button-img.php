<div class="shareyourcart-button" 
<?php if(isset($callback_url) && !empty($callback_url)): ?>
data-syc-callback_url="<?php echo $callback_url; ?>"
<?php endif; ?> 
data-syc-layout="custom"
><img class="syc-button-img" src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/pixel.gif'); ?>" /></div>

<?php

    // If only the hover is uploaded
    if((!$button_img or !$button_img_width or !$button_img_height) and ($button_img_hover and $button_img_hover_width and $button_img_hover_height)) {
        $button_img = $button_img_hover;
        $button_img_width = $button_img_hover_width;
        $button_img_height = $button_img_hover_height;
        $button_img_hover = null;
        $button_img_hover_width = null;
        $button_img_hover_height = null;
    }

?>

<style>
	.syc-button-img {
		cursor:pointer;
		background:url('<?php echo $button_img;?>');
		width: <?php echo $button_img_width; ?>px;
		height: <?php echo $button_img_height; ?>px;
	}
	.syc-button-img:hover {
                <?php
                    if($button_img_hover and $button_img_hover_width and $button_img_hover_height) {
                ?>
		background:url('<?php echo $button_img_hover; ?>');
		width: <?php echo $button_img_hover_width; ?>px;
		height: <?php echo $button_img_hover_height; ?>px;
                <?php
                    }
                ?>
	}
        
</style>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>