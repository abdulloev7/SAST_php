<?php

function FilterInt($sInput) {
	return (int)intval(trim($sInput));
}

function FilterFloat($sInput) {
	return (float)floatval(trim($sInput));
}

function FilterString($sInput) {
	$sInput = strip_tags(trim($sInput));
	return $sInput;
}

function LegacyFilterInput($sInput, $type = 'string', $size = 1) {
	if (strlen($sInput) > 0) {
		switch ($type) {
			case 'int':
				$return = FilterInt($sInput);
				return $return;
			case 'float':
				return FilterFloat($sInput);
			case 'string':
				return FilterString($sInput);
		}
	} else {
		return '';
	}
}

$conn = new mysqli();

$sql1 = LegacyFilterInput($_GET['groupId'], 'int');
mysqli_query($conn, $sql1);

$sql2 = LegacyFilterInput($_GET['groupId']);
mysqli_query($conn, $sql2);

$sql3 = LegacyFilterInput($_GET['groupId'], 'float');
mysqli_query($conn, $sql3); 