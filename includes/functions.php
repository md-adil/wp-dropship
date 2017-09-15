<?php
use Bigly\Dropship\Config;

function blds_remote_request($method, $url, $options = []) {
	$base = Config::get('remote.base');
	$accessToken = get_option(Config::get('options.access_token'));
	$options['headers']['Authorization'] = 'Bearer ' . $accessToken;
	$options['headers']['Accept'] = 'application/json';
	return call_user_func('wp_remote_' . $method, "{$base}/{$url}", $options);
}

function blds_remote_get($url, $options = []) {
	return blds_remote_request('get', $url, $options);
}

function blds_remote_post() {
	return blds_remote_request('post', $url, $options);
}
