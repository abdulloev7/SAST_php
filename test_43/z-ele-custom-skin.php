<?php

function get_document_data(){

	global $testObj;
	$testObj->get_results($_POST['query'] ); 

}

get_document_data();