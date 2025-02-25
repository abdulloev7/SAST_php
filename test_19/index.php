<?php

function test1() {
	$date = $_POST["q"];
	if (!is_numeric($date)) {
		$date = intval($date);
	}
	mysqli_query($conn, "select {$date}"); 
}

function test2() {
	$date = $_POST["q"];
	mysqli_query($conn, "select {$date}");
}
test1();
test2();