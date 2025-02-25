<?php

function require_wp_db_test() {
	global $testObj;
	$testObj = new testDb();
}
require_wp_db_test();


function apply_filters_test( $value){
	return $value;
}