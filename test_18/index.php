<?php

function fallthroughContext(){
	$date2 = $_GET["w"];
	if (!is_int($date2)) {
		$date2 = intval($date2);
	}
	return $date2;
}

function fallthroughContext2(){
	$date = $_POST["q"];
	if (!is_int($date)) {
		$date = date($date);
	}
	return $date;
}

$date = $_POST["q"];
if (!is_int($date)) {
	$date = intval($date);
}
mysqli_query($conn, "select {$date}"); 


mysqli_query($conn, fallthroughContext()); 
mysqli_query($conn, fallthroughContext2());

