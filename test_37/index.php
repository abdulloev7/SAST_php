<?php
function byGet() {
	$mysqli = new mysqli("", "", "", "");
	$login = $_GET["login"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql); 
}

function byPost() {
	$mysqli = new mysqli("", "", "", "");
	$login = $_POST["login"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function byArgv() {
	global $argv;
	$mysqli = new mysqli("", "", "", "");
	$login = $argv[2];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function byCookie() {
	$mysqli = new mysqli("", "", "", "");
	$login = $_COOKIE["login"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function byRequest() {
	$mysqli = new mysqli("", "", "", "");
	$login = $_REQUEST["login"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function byFiles() {
	$mysqli = new mysqli("", "", "", "");
	$login = $_FILES["file"]["name"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function byHeader() {
	$mysqli = new mysqli("", "", "", "");
	$headers = getallheaders();
	$login = $headers["Auth-Token"];
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}

function bySession() {
	$mysqli = new mysqli("", "", "", "");
	$login = session_id();
	$sql = "select name from usese where login={$login}";
	mysqli_query($mysqli, $sql);
}
