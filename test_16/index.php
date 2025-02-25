<?php

function checkSafesContext() {
	$username1 = "name";
	$password2 = md5("pass");

	$data = [
		"key" => "value",
		"key2" => "value2",
	];
	$var1 = "select name";

	extract($data);

	$conn = new PDO();
	$conn->query($var1);
	$conn->query("SELECT * from users where username = '$username1' and password = md5('$password2')");
}

function checkInLocalContext() {
	$var1 = "select name";

	extract($_POST);

	$conn = new PDO();
	$conn->query($var1); 
	$conn->query("SELECT * from users where username = '$username1' and password = md5('$password2')");
}

$sql = "select name";

extract($_POST);

$conn = new PDO();
$conn->query($sql); 
$conn->query("SELECT * from users where username = '$username' and password = md5('$password')");

checkInLocalContext();