<?php

$filter = $_GET["filter"];
$data = "text text text";
if (!preg_match("!search-{$filter}!", $data)) {
	echo "Fail";
}

$replacedData = preg_replace("!search-{$filter}!", "sometext", $data);