<?php

function getData() {
	return unserialize($_COOKIE['name']); 
}

getData();