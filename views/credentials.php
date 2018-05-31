<?php
require(__DIR__ . '/../includes/header.php'); ?>
<style>
	#page-biglydropship-api-credentials table {
		max-width: 1000px;
		width: 80%;
	}
	#page-biglydropship-api-credentials input {
		padding: 10px;
		width: 100%;
	}
	.success-message {
		color: #25ad25;
	}

	.progress-message {
		color: #00F;
	}

</style>

<div class="wrap" id="page-biglydropship-api-credentials">
	<form method="post" id="form-store-credential">
		<div class="message-box"></div>
		<input type="hidden" name="action" value="blds_access-token">
		<input type="hidden" name="grant_type" value="password">
		<table>
			<tbody>
				<tr>
					<td>Client ID</td>
					<td>
						<input type="text" name="client_id" placeholder="Client ID" required>
					</td>
				</tr>
				<tr>
					<td>Secret Key</td>
					<td>
						<input type="text" name="client_secret" placeholder="Secret key" required >
					</td>
				</tr>
				<tr>
					<td>Email</td>
					<td>
						<input type="email" name="username" placeholder="Email / Username" required>
					</td>
				</tr>
				<tr>
					<td>Password</td>
					<td>
						<input type="password" name="password" type="password" placeholder="Password" required>
					</td>
				</tr>
				
				<tr>
					<td></td>
					<td>
						<button type="submit" class="button button-primary">Get Access Token</button>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
<script>
(function($) {
	var $btnSubmit = $('button:submit'), $messageBox = $('.message-box');
	function addMessage(message, type) {
		type = type || 'progress';
		var className = type + '-message';
		$('<div />', {class: type + '-message', text: message}).appendTo($messageBox);
	}

	function clearMessage() {
		$messageBox.html('');
	}

	$('#form-store-credential').on('submit', function(e) {
		e.preventDefault();
		clearMessage();
		sendLoginRequest(this)
	});

	function sendLoginRequest(form) {
		if($btnSubmit.hasClass('blds-preloader')) {
			return;
		}
		addMessage('Authenticating...');
		$btnSubmit.addClass('blds-preloader');
		$.post('admin-ajax.php', $(form).serialize(), function(res) {
			handleLoginResponse(res, form);
		});
	}

	function handleLoginResponse(res, form) {
		if(res.status == 'fail') {
			addMessage(res.message, 'error');
			$btnSubmit.removeClass('blds-preloader');
		} else {
			addMessage('Authenticated Successfully!', 'success');
			registerWebhook(form);
		}
	}

	function registerWebhook(form) {
		addMessage('Registering Webhook...');
		$.post('admin-ajax.php', { action: 'blds_register-webhook' }, function(res) {
			$btnSubmit.removeClass('blds-preloader');
			if(res.status === 'ok') {
				addMessage('Webhook registered successfully!', 'success')
				addMessage('Congratulation! Bigly has been successfully integrated with your store, take a breath while we are syncing all your products.')
				form.reset();
			} else {
				addMessage(res.message, 'error');
			}
		});
	}
})(jQuery)
</script>