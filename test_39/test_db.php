<?php

class testDb {

	public function test_mysqlq($query) {
		$query = trim($query);
		mysqli_query($conn, $query);
	}
}

function test_wpdb() {
	$testObj = new testDb('', '', '', '');

	$sql = "UPDATE tablename SET column1='testdata' WHERE id=" . $_GET["id"];
	$testObj->test_mysqlq($sql); 
}

test_wpdb();

