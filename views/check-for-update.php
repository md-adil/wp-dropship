<style>
    .notice.notice-blink p {
        animation: __blds_blink 1s infinite;
    }
    @keyframes __blds_blink {
        0% {
            opacity: 0;
        }
        50% {
            opacity: 1;
        }
        100% {
            opacity: 0;
        }
    }
</style>
<div class="wrap" style="width: 30%; margin: 0 auto">

    <div id="blds-notification" class="notice notice-warning is-dismissible notice-blink">


        <p>Bigly Dropship Syncing Products...</p>
    </div>
</div>
<script>
(function($) {
    var notifier = $('#blds-notification');
    notifier.hide();
    function sendRequest() {
        notifier.show();
	$.post('admin-ajax.php', {action: 'blds_sync'}, function(res) {
            notifier.hide();
	if(res.status === 'ok') {
		return handleResponse(res);
	}
	if(res.redirect) {
		if( res.message && !confirm(res.message)) {
			return;
		}
		window.location.href = res.redirect;
return;
			}
            alert(res.message);
		}).always(function() {
			
		});
	}

	function handleResponse(res) {
		if(res.hasMore) {
			page ++;
			sendRequest();
		} else {
            window.location.reload();
        }
	}
    // sendRequest();
})(jQuery);
</script>
