<?php
require(__DIR__ . '/../includes/header.php');
?>
<style>
	#page-biglydropship-api-credentials table {
		max-width: 1000px;
		width: 80%;
	}
	#page-biglydropship-api-credentials input {
		padding: 10px;
		width: 100%;
	}
</style>

<div class="wrap" id="page-biglydropship-api-credentials">
	<form method="post" id="form-store-credential">
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
						<input type="email" name="email" placeholder="Email / Username" required>
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
				<tr class="error-box">
					<td></td>
					<td width="800" class="error-message"></td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
<script>
	(function($) {
		$('#form-store-credential').on('submit', function(e) {
			e.preventDefault();
			var btn = $('button:submit');
			if(btn.hasClass('blds-preloader')) return;
			btn.addClass('blds-preloader');
			console.log('Submitting');
			$.post('admin-ajax.php', $(this).serialize(), function(res) {
				btn.removeClass('blds-preloader');
				if(res.status == 'fail') {
					$('.error-box').find('.error-message').text(res.message);
				} else {
					alert('Authorization key has been updated, sync the products');
					$('.error-box').find('.error-message').text('');
				}
			});		
		})
	})(jQuery)
</script>