<?php

mysql_connect('host', 'user', 'pass');

$input = $_GET["id"];
$id = mysql_escape_string($input);
$sql = "select * from table where id={$id}";
mysql_query($sql);  

$sql = "select * from table where id='{$id}'";
mysql_query($sql);


$id = mysql_real_escape_string($input);
$sql = "select * from table where id={$id}";
mysql_query($sql);

$sql = "select * from table where id='{$id}'";
mysql_query($sql);

$sql = "select * from table where id='{$input}'";
mysql_query($sql);