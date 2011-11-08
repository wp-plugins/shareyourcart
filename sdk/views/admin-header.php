<link rel="stylesheet" type="text/css" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/admin-style.css'); ?>" />
<script>
window.onload = function() {
document.getElementById('syc-form').addEventListener('submit', changetext, false);
}; 

var changetext = function(){
	var textarea = document.getElementById('syc_button_textarea').value;
	document.getElementById('syc_button_textarea').value = escape(textarea);	
}
</script>