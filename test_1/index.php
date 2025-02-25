<?php

mysql_connect('host', 'user', 'pass');

$input = $_GET["id"];
$id = addslashes($input);
$sql1 = "select * from table where id={$id}";
mysql_query($sql1); 

$sql2 = "select * from table where id='{$id}'";
mysql_query($sql2);


$id = addcslashes($input);
$sql3 = "select * from table where id={$id}";
mysql_query($sql3); 

$sql4 = "select * from table where id='{$id}'";
mysql_query($sql4);