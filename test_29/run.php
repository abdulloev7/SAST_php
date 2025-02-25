<?php

function runLimit($limit) {
	return App::pdo()->fetch("SELECT * FROM members Where USERID = 1 ORDER BY Username LIMIT $limit");
}


$user = runLimit($_GET["limit"]); 

$user = runLimit($_POST["limit"]);

$user = runLimit($_REQUEST["limit"]);

$user = runLimit(50); 