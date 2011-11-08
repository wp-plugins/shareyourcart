<div class="shareyourcart-button <?php echo ( $this->getConfigValue('button_position') == 'floating'  ? 'button_iframe' : 'button_iframe-normal');?>" 
<?php if(isset($callback_url) && !empty($callback_url)): ?>
data-syc-callback_url="<?php echo $callback_url; ?>"
<?php endif; ?> 
data-syc-skin="<?php echo $this->getConfigValue('button_skin');?>" 
data-syc-orientation="<?php echo $this->getConfigValue('button_position'); ?>" 
></div>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>