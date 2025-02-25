<?php

class testDb {

	private $conn;

	public function apply_filters_test($query) {
		return $query;
	}

	public function test_mysqlq($query) {
		$query = $this->apply_filters_test($query);
		//$query = $query2;
		//$this->query( $query);
		mysqli_query($this->conn, $query);
	}
}
/*
function test_wpdb() {
	$testObj = new testDb('', '', '', '');

	$sql = "UPDATE tablename SET column1='testdata' WHERE id=" . $_GET["id"];
	$testObj->test_mysqlq($sql);
}

test_wpdb();*/
