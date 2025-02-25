<?php

function get_document_data(){

	global $testObj;

	$testObj->get_results($_POST['query'] );
	$testObj->query($_POST['query'] );
	$testObj->test_mysqlq($_POST['query'] );
	$testObj->_do_query($_POST['query'] );

}
get_document_data();