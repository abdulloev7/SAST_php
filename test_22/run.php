<?php

function runTest() {
	$object = new DBClass();
	if ($object instanceof PDO) {
		$object->run("SELECT * FROM `table` " . $_GET['id']);
	}

	$object->query("SELECT * FROM `table` " . $_GET['id']);
}

runTest();