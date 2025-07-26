<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "lewis_car_wash";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
