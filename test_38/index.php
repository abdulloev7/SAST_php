<?php

function runSingle() {

	$id = $_GET['id'];

	$ids1 = Params::toType($id, Params::TYPE_UINT); 
	mysqli_query($conn, implode(" and", $ids1));

	$ids2 = Params::toInt($id);
	mysqli_query($conn, $ids2);

	$ids3 = Params::toUint($id);
	mysqli_query($conn, $ids3);

	mysqli_query($conn, $_GET["q"]); 
}

runSingle();