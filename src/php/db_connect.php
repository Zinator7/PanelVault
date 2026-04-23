<?php

$host = 'localhost';
$dbname = 'db_panelvault';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");

?>