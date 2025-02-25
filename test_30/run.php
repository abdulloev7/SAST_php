<?php

function getUserField($field) {
	$conn = new DataBase();
	return $conn->getColumn("SELECT *, $field FROM members Where USERID = 1");
}
$user = getUserField($_GET["field"]); 

$user = getUserField($_POST["field"]); 

$user = getUserField($_REQUEST["field"]); 

$user = getUserField("username");