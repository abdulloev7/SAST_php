<?php

function get_value($arr, $name, $type, $default = false) {
	if (!isset($arr[$name]))
		return $default;
	switch ($type) {
		case 'int':
			$return = intval($arr[$name]);
			break;
		case 'float':
			$return = floatval($arr[$name]);
			break;
		case 'str':
			$return = trim($arr[$name]);
			break;
		case 'array':
			if (is_array($arr[$name])) {
				$return = $arr[$name];
			}
			break;
		default:
			$return = $default;
			break;
	}
	return $return;
}

function get($name, $type) {
	return get_value($_GET, $name, $type);
}

function getInt($name, $num) {
	return get($name, 'int');
}

function getFloat($name) {
	return get($name, 'float');
}

function getStr($name) {
	return get($name, 'str');
}

function printData(){
	$T=23;
}

function run() {
	$sql1 = getInt("a", 0); 
	mysqli_query($conn, $sql1); 

	$sql2 = getFloat("a");
	mysqli_query($conn, $sql2); 

	$sql3 = getStr("a");
	mysqli_query($conn, $sql3); 
}

run();



