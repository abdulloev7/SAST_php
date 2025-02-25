<?php

ini_set("display_errors", 1);
error_reporting(E_ALL);

function get($callback, $value) {
	return $callback($value);
}

function getValue($num) {
	return intval($num);
}

function getData() {
	return get(fn($result) => "{$result} {$_GET["q"]}", 0);
}

$sql = getData();