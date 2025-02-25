<?php

function getSession() {
	return session_id();
}

function getId() {
	return getSession();
}

$sql = getId();

mysqli_query($conn, $sql);