<?php

function testIntval() {
	$date = $_POST["q"];
	if (intval($date) != $date) {
		$date = intval($date);
	}
	mysqli_query($conn, "select {$date}");
}

function testFloatval() {
	$date = $_POST["q"];
	if (floatval($date) != $date) {
		$date = floatval($date);
	}
	mysqli_query($conn, "select {$date}"); 
}

function testStrval() {
	$date = $_POST["q"];
	if (strval($date) != $date) {
		$date = strval($date);
	}
	mysqli_query($conn, "select {$date}");
}

testIntval();
testFloatval();
testStrval();