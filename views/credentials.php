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
		<input type="hidden" name="action" value="biglydropship_store-credential">
		<input type="hidden" name="id" value="<?= $id ?>">
		<table>
			<tbody>
				<tr>
					<td>Client ID</td>
					<td>
						<input type="text" name="client_id" placeholder="Client ID" value="<?= $clientId ?>">
					</td>
				</tr>
				<tr>
					<td>Secret Key</td>
					<td>
						<input type="text" name="secret_key" placeholder="Secret key" value="<?= $clientSecret ?>">
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<button type="submit">Save</button>
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
			$.post('admin-ajax.php', $(this).serialize(), function(res) {
				if(res.redirect) {
					window.location.href = res.redirect;
				}
				console.log(res);
			})			
		})
	})(jQuery)
</script>