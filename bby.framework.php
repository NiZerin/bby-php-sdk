<?php
ini_set("display_errors","on");
include 'bby.php';

class bbyFrameworkAdapter extends bbyAdapter
{
	function _send($data, $method){
		return parent::_send($data, $method);
	}
	function _recv($result, $method){
		return parent::_recv($result, $method);
	}
}

class bbyFramework extends bbyApp
{
	function __construct($token){
		parent::__construct($token, 10, new bbyFrameworkAdapter);
	}
	
	function call($method, $params){
		$data = ['m'=>$method, 'p'=>$params];
		return parent::request($data);
	}
	
}