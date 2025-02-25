<?php

function test1() {
	$date_start = $_POST['date_start'];
	if (!empty($date_start)) {
		$date_start = date("Y-m-d H:i:00", strtotime($date_start));
	}

	mysqli_query($conn, $date_start);
}

function test2() {
	$date_start = $_POST['date_start'];
	if ($date_start != '') {
		$date_start = date("Y-m-d H:i:00", strtotime($date_start));
	}

	mysqli_query($conn, $date_start);
}

function test3() {
	$date_start = $_POST['date_start'];
	$date_start = date("Y-m-d H:i:00", strtotime($date_start));

	mysqli_query($conn, $date_start);
}

function test4() {
	$date_start = $_POST['date_start'];
	$date_start = date($_POST['format'], strtotime($date_start));

	mysqli_query($conn, $date_start); 
}

test1();
test2();
test3();
test4();