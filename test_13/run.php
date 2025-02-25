<?php

class CallbacksFilter {

	private array $callbacks;

	public function registerCallback($name, $function) {
		$this->callbacks[$name] = $function;
		$testValue = $this->callbacks[$name];
	}

	public function doCallback($name, $param1, $param2) {
		$this->callbacks[$name]($param1, $param2);
	}

	public function doCallbackCallUserFunc($name, $param1, $param2) {
		call_user_func($this->callbacks[$name], $param1, $param2);
	}

	public function doCallbackCallArray($name, $param1, $param2) {
		call_user_func_array($this->callbacks[$name], [$param1, $param2]);
	}

	public function doCallbackCallArrayV2($name, ...$args) {
		call_user_func_array($this->callbacks[$name], $args);
	}

}

function testFuncName($str, $param) {
	mysqli_query($conn, $param);
}

function testFuncNameTrue($str, $para){
	return true;
}

$callBacksObj = new CallbacksFilter();
$callBacksObj->registerCallback("test", "testFuncNameTrue"); 
$callBacksObj->registerCallback("test", "testFuncName");
$callBacksObj->registerCallback("test", "testFuncNameTrue");

$callBacksObj->doCallback("test", "a", "g");
$callBacksObj->doCallback("test2", "a", $_GET["q"]);
$callBacksObj->doCallback("test", "a", $_GET["q"]);
$callBacksObj->doCallback("dfkjhkjh", "a", $_POST["t"]);
$callBacksObj->doCallbackCallUserFunc("dfkjhkjh", "a", $_POST["t"]);
$callBacksObj->doCallbackCallUserFunc("test", "a", $_POST["t"]); 
$callBacksObj->doCallbackCallArray("test", "a", "b");
$callBacksObj->doCallbackCallArray("test", "a", $_GET["name"]);
$callBacksObj->doCallbackCallArray("test3", "a", $_GET["name"]); 
$callBacksObj->doCallbackCallArrayV2("test", "a", $_GET["name"]);