<?php
// database connection start

$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "tongdokan";

// connect to mysql
$conn = new mysqli($host, $user, $pass, $dbname);

// stop if connection fails
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// database connection end
?>
