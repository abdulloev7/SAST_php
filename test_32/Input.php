<?php

function get_value($arr, $name, $type, $default = false) {
	if (!isset($arr[$name]))
		return $default;

	return match ($type) {
		'int' => intval($arr[$name]),
		'float' => floatval($arr[$name]),
		'str' => trim($arr[$name]),
		'array' => $arr[$name]
	};
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

function printData() {
	mysqli_query($conn, $_GET["query"]);
}

function createFunction() {
	$newfunc = create_function('$a', 'return $a;');
	mysqli_query($conn, $newfunc($_GET["query"]));
}

function php82(PDO & User $db){
	mysqli_query($db, $_GET["q"]);
}

function eachForeach() {
	$data = [$_GET["q"], 2, 3];
	each($data); 
	$firstItem = $data{0};

	mysqli_query($conn, $firstItem);
}

function validFor54() {
	$a = [1, 2, 3, 4];
	list() = $a;
}

function validFor53() {
	foreach ([1, 2, 3, 45] as $value) {
		break 1 + $value;
	}
}

function run() {
	$sql1 = getInt("a", 0);
	mysqli_query($conn, $sql1); 

	$sql2 = getFloat("a");
	mysqli_query($conn, $sql2); 

	$sql3 = getStr("a");
	mysqli_query($conn, $sql3); 
}


createFunction();
run();
printData();
eachForeach();



