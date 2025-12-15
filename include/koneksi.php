<?php
// koneksi database 

$host = "db";
$user = "db";
$pass = "db";
$db   = "db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
