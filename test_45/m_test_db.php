<?php

function get_document_data() {
	global $testObj;

	$testObj->test_mysqlq($_POST['query']); 
}

get_document_data();