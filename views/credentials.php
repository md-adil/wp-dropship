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
						<input type="text" name="client_id" placeholder="Client ID">
					</td>
				</tr>
				<tr>
					<td>Secret Key</td>
					<td>
						<input type="text" name="secret_key" placeholder="Secret key">
					</td>
				</tr>
				<tr>
					<td>Email</td>
					<td>
						<input type="text" name="username" placeholder="Email / Username">
					</td>
				</tr>
				<tr>
					<td>Password</td>
					<td>
						<input type="text" name="password" type="password" placeholder="Password">
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
		$('#form-store-credential').on('submit', function(e) {
			e.preventDefault();
			var btn = $('button:submit');
			if(btn.hasClass('blds-preloader')) return;
			btn.addClass('blds-preloader');
			console.log('Submitting');
			$.post('admin-ajax.php', $(this).serialize(), function(res) {
				btn.removeClass('blds-preloader');
				console.log(res);
			});		
		})
	})(jQuery)
</script>