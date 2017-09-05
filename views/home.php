<?php
require(__DIR__ . '/../includes/header.php');
?>
<div class="wrap">
	<button class="button button-primary" id="blds-sync-product">Sync Products</button>
	<div class="success-message"></div>
</div>

<script>
(function($) {
	var btn = $('#blds-sync-product');
	btn.click(function() {
		sendRequest()
	})

	function sendRequest() {
		btn.addClass('blds-preloader');
		$.post('admin-ajax.php', {action: 'blds_sync'}, function(res) {
			handleResponse(res);
			btn.removeClass('blds-preloader');
		})
	}

	function handleResponse(res) {
		console.log('REsponse', res);
	}
})(jQuery);
</script>