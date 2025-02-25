<?php

$query = "select * from table where id=" . $_GET['id'];
mysql_query($query);