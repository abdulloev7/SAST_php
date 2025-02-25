<?php

$date_start = $_POST["date"];
$date_start = date("Y-m-d H:i:00", $date_start);
mysqli_query($conn, $date_start); 

$date_end = $_POST["date"];
$date_end = date($_POST["format"], $date_end);
mysqli_query($conn, $date_end);