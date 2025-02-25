<?php

function test_mysqlq($queries) {
	runQ($queries);
}

function runQ($queries) {
	foreach ($queries as $key => $query) {
		run_mysqli_query($query);
	}
}

function run_mysqli_query($query) {
	mysqli_query($conn, $query);
}

function run_single($sqls) {
	run_mysqli_query($sqls["q"]);
}

function run_singleT($sqls) {
	run_mysqli_query($sqls["t"]);
}

function run_singleW($sqls) {
	run_singleT($sqls["w"]);
}

function run_single3($sqls) {
	run_mysqli_query($sqls);
}

function run_single_q($sqls) {
	run_mysqli_query($sqls);
}

function test_wpdb() {
	$param = array(
		'w' => [
			't' => $_POST["q"],
		]
	);

	runQ($param);
	run_mysqli_query($param); 
	run_mysqli_query($param["w"]);
	run_mysqli_query($param["w"]["t"]);
	run_mysqli_query($param["w"]["r"]);
	run_single($param); 
	run_single_q($param["q"]); 
	run_singleW($param);
	run_single3($param["w"]); 
}

test_wpdb();
