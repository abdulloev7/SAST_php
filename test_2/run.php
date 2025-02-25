<?php

$sql1 = test_filters($_GET["q"]);
mysqli_query($conn, $sql1); 

$sql2 = test_filters($_GET);
mysqli_query($conn, $sql2);