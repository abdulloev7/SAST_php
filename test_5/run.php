<?php

function testFuncName($str, $param) {
	mysqli_query($conn, $param);
}

function registerCallback($name, $function) {

}

$var="clean data";
testFuncName("True", $var); 
testFuncName("True", $_GET["q"]);
call_user_func('testFuncName', "True", $_GET["q"]); 
call_user_func_array('testFuncName', ["True", $_GET["q"]]); 