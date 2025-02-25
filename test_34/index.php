<?php

$object = [
	'sf' => $_GET["q"],
];
$testSQL = new TestClass($object);
$testSQL->run();