<?php
namespace Bigly\Dropship\Library;

/**
*
*/
class Client
{
    protected $headers = [
        'Accept' => 'application/json'
    ];

    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setHeader($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function request($method = 'GET', $uri, $options = array())
    {
        $base = $this->config->get('remote.base');
        foreach ($this->headers as $key => $val) {
            $options['headers'][$key] = $val;
        }
        return call_user_func('wp_remote_' . strtolower($method), "{$base}/{$uri}", $options);
    }

    public function withAuth()
    {
        $accessToken = get_option($this->config->get('options.access_token'));
        $clone = clone $this;
        $clone->setHeader([
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
        return $clone;
    }

    public function put($uri, $options = [])
    {
        if(!isset($options['body'])) {
            $options['body']  = [];
        }
        $options['body']['_method'] = 'put';
        return $this->request('post', $uri, $options);
    }

    public function __call($fn, $args)
    {
        $options = array();

        if (!count($args)) {
            throw new \Exception("Client URL is required", 1001);
        }

        if (isset($args[1])) {
            $options = $args[1];
        }
        return $this->request($fn, $args[0], $options);
    }
}
