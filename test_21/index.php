<?php

function getHeaders() {
	return getallheaders();
}

function getHeader() {
	$heads = getHeaders();
	return $heads["q"];
}

$sql = getHeader();
mysqli_query($conn, $sql);