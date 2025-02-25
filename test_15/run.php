<?php


function runLimit($limit) {
	$sql = "SELECT * FROM members Where USERID = 1 ORDER BY Username LIMIT $limit";

	$pdoObj = new ExtendPDO("mysql:host=;dbname=;charset=utf8", "", "", []);
	$stmt = $pdoObj->prepare($sql);
	return $stmt->execute();
}


$user = runLimit($_GET["limit"]);

$user = runLimit($_POST["limit"]);

$user = runLimit($_REQUEST["limit"]); 

$user = runLimit(50);