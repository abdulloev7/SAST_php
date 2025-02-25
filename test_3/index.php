<?php

class Filter {
	public static function filterInput($value) {
		return intval($value);
	}
}

class FilterObject {

	public function trimInput($value) {
		return trim($value);
	}

	public function filterInput($value) {
		return intval($value);
	}
}

function filterInput($value) {
	return intval($value);
}

$conn = new mysqli("localhost", "root", "", "test");

list($sid1, $cid1) = $_POST["sid_cid"];
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid1}, {$cid1})"); 

list($sid2, $cid2) = array_map('intval', $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid2}, {$cid2})");

list($sid21, $cid21) = array_map('trim', $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid21}, {$cid21})");

list($sid211, $cid211) = array_map('intval', explode("_", $_POST["sid_cid"]));
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid211}, {$cid211})");

list($sid212, $cid212) = array_map('trim', explode("_", $_POST["sid_cid"]));
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid212}, {$cid212})");

[$sid3, $cid3] = array_map(function($value) { 
	return intval($value);
}, $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid3}, {$cid3})");


[$sid31, $cid31] = array_map(function($value) {
	return trim($value);
}, $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid31}, {$cid31})");


list($sid4, $cid4) = array_map('filterInput', $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid4}, {$cid4})");

list($sid5, $cid5) = array_map('Filter::filterInput', $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid5}, {$cid5})");

list($sid6, $cid6) = array_map(['Filter', 'filterInput'], $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid6}, {$cid6})");

$filterObject = new FilterObject();
list($sid7, $cid7) = array_map([$filterObject, 'filterInput'], $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid7}, {$cid7})");

$filterObject = new FilterObject();
list($sid8, $cid8) = array_map([$filterObject, 'trimInput'], $_POST["sid_cid"]);
mysqli_query($conn, "INSERT INTO `table` (`sid`, `cid`) VALUES ({$sid8}, {$cid8})"); 