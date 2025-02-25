<?php

$mysqli = new mysqli("database", "root", "123");
$sql = "set names gbk";
$mysqli->query($sql);

$sql = "set names 'gbk'";
$mysqli->query($sql); 

$sql = 'set names "gbk"';
$mysqli->query($sql); 

$sql = 'set names 
			gbk';
$mysqli->query($sql); 

$mysqli->query("set names gbk"); 

$charset="gbk";
$mysqli->query("set names {$charset}");

mysqli_query($mysqli, "set names gbk"); 
mysqli_query($mysqli, "set names big5");
mysqli_query($mysqli, "set names cp932");
mysqli_query($mysqli, "set names gb2312");
mysqli_query($mysqli, "set names sjis");

mysqli_query($mysqli, "set names utf8"); 

mysqli_query($mysqli, $_GET["id"]);