<?php

class testDb {

	public function test_mysqlq($query) {
		$query = filter_data($query, 6);
		mysqli_query($conn, $query);
	}
}