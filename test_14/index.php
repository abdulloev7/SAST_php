<?php

$inputId = $_GET["id"];
$inputName = mysql_escape_string($_GET["name"]);

$id1 = mysql_escape_string($inputId);
$sql1 = "select * from table where id={$id1}";
mysql_query($sql1);

$sql2 = "select * from table where id=" . $id1;
mysql_query($sql2); 

$sql3 = "select * from table where id='{$id1}'";
mysql_query($sql3);

$sql4 = "select * from table where id='" . $id1 . "'";
mysql_query($sql4); 

$sql5 = "select * from table where id=" . $id1 . " and name='{$inputName}'";
mysql_query($sql5);

$sql6 = "select * from table where id='" . $id1 . "' and name='{$inputName}'";
mysql_query($sql6);

$sql7 = "select * from table where fio='John' id='" . $id1 . "' and name='{$inputName}'";
mysql_query($sql7);

$sql8 = "select * from table where fio='John' id='" . $id1 . "' and name=" . $inputName;
mysql_query($sql8); 

$sql9 = "select * from table where fio='John' id='{$id1}' and name=" . $inputName;
mysql_query($sql9); 


$id2 = "'{$id1}'";
$sql10 = "select * from table where id={$id2}";
mysql_query($sql10);

$sql11 = "select * from table where id='{$id1}' and name='{$inputName}'";
mysql_query($sql11); 

$sql12 = "select * from table where id={$id1} and name='{$inputName}'";
mysql_query($sql12); 

$sql13 = "select * from table where id='{$id1}' and name={$inputName}";
mysql_query($sql13);

$sql14 = "select * from table where id='name_{$id1}'";
mysql_query($sql14);

$sql15 = "select * from table where id='{$id1}_name'";
mysql_query($sql15);

$sql16 = "select * from table where id='name_{$id1}_login'";
mysql_query($sql16); 


$baseQuery = "select * from table where id=";
$sql17 = "{$baseQuery}{$id1}";
mysql_query($sql17); 

$sql18 = "$baseQuery$id1";
mysql_query($sql18); 

$sql19 = "$baseQuery$inputName";
mysql_query($sql19); 

$sql20 = "select * from table where id={$inputId}";
mysql_query($sql20);

