<?php

function getDateFalse1() {
	$date_start = "";
	if (!true) {
		$date_start = $_POST["date"];
	}
	return $date_start;
}

function getDateFalse2() {
	$date_start = "";
	if (false) {
		$date_start = $_POST["date"];
	}
	return $date_start;
}

function getDateTrue1() {
	$date_start = "";
	if (!false) {
		$date_start = $_POST["date"];
	}
	return $date_start;
}

function getDateTrue2() {
	$date_start = "";
	if (!false) {
		$date_start = $_POST["date"];
	}
	return $date_start;
}

function getDateTrue2Clean() {
	$date_start = $_POST["date"];
	if (!false) {
		$date_start = "";
	}
	return $date_start;
}

mysqli_query($conn, getDateFalse1());
mysqli_query($conn, getDateFalse2());
mysqli_query($conn, getDateTrue1());
mysqli_query($conn, getDateTrue2());
mysqli_query($conn, getDateTrue2Clean());

