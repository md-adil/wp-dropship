<?php
require(__DIR__ . '/../includes/header.php');
?>
<style>
	.success-message .success {
		color: #0f5f0f;
	}.success-message .warning {
		color: #9c9c00;
	}.success-message .error {
		color: #d80000;
	}
</style>
<div class="wrap">
	<button class="button button-primary" id="blds-sync-product">Sync Products</button>
	<div class="success-message">
	</div>
</div>

<script>
(function($) {
	var btn = $('#blds-sync-product');
	var page = 1;
	var $msg = $('.success-message');
	btn.click(function() {
		sendRequest()
	})

	function sendRequest() {
		btn.addClass('blds-preloader');
		addMessage("Sending request for page " + page, 'warning');
		$.post('admin-ajax.php', {action: 'blds_sync'}, function(res) {
			if(res.status === 'ok') {
				return handleResponse(res);
			}
			if(res.redirect) {
				if( res.message && !confirm(res.message)) {
					return;
				}
				window.location.href = res.redirect;
			}
		}).always(function() {
			btn.removeClass('blds-preloader');
		});
	}

	function handleResponse(res) {
		addMessage("Data synced: Category - " + res.data.categories + ', Product - ' + res.data.products);
		if(res.hasMore) {
			page ++;
			sendRequest();
		}
	}

	function addMessage(message, type) {
		type = type || 'success';
		$('<p />', {class: type}).text(message).appendTo($msg);
	}
	
})(jQuery);
</script>