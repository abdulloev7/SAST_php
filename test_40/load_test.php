<?php

function require_wp_db_test() {
	global $testObj;
	$testObj = new wpdb('', '', '', '');
}
require_wp_db_test();


function apply_filters_test( $value){
	return $value;
}