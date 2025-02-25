<?php

$str = $_GET["q"];

mysqli_query($conn, $str); 
mysqli_query($conn, sha1($str));
mysqli_query($conn, md5($str));
mysqli_query($conn, hash('md5', $str));
mysqli_query($conn, password_hash($str, 'md5'));
mysqli_query($conn, crypt($str, 'salt'));
mysqli_query($conn, mcrypt_encrypt($str, 'salt'));
mysqli_query($conn, openssl_encrypt($str, 'salt', 'pass'));