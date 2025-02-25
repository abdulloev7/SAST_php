<?php

function getName() {
	return htmlentities($_REQUEST["name"]);
}

function getSurname() {
	return htmlentities($_REQUEST["surname"]);
}

function getParams() {
	$params = [
		"name" => getName(),
		"surname" => getSurname(),
	];
	foreach ($params as $key => $value) {
		$params[$key] = htmlspecialchars($value);
	}
	return $params;
}

print_r(getParams());