<?php

function get_document_data() {
	global $wp_query;
	$wp_query = new \WP_Query($_POST['query']);
	$wp_query->get_posts();
}

function test_global_wpdb() {
	global $wpdb;

	$wpdb->get_results($_POST['query']); 
}

test_global_wpdb();

function test_wpdb() {
	$testObj = new wpdb('', '', '', '');

	$sql = "UPDATE tablename SET column1='testdata' WHERE id=" . $_GET["id"];
	$testObj->get_results($sql);
	$testObj->query($sql);
}

test_wpdb();