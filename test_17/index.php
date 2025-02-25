<?php

$conn = new mysqli();

$cleanSQL = "select lalala from table";
$result = mysqli_query($conn, $cleanSQL);

while ($aRow = mysqli_fetch_array($result)) {
	extract($aRow);

	$temp = "SELECT plg_plgID FROM table 
            WHERE plg_FamID='$fam_ID' AND plg_PledgeOrPayment='Pledge' AND plg_FYID=$iFYID";
	mysqli_query($conn, $temp);

}

mysqli_query($conn, $_GET["q"]);