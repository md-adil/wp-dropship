<?php
/**
* 
*/
class Client
{
	
	function __construct(argument)
	{
		# code...
	}

	public function request($method = 'GET', $uri, $options = array()) {

	}

	public function __call($fn, $args) {
		return $this->request($fn, $args[0], $args[1]);
	}
}
