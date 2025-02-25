<?php

if (true) {
	$sql = "select * from table where id=" . $_GET['id'];
	mysqli_query($conn, $sql);
} else { ?>
	Simple text
	<?
}
