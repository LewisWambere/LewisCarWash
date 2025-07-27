<?php // Database connection
$conn = new mysqli('localhost', 'root', '', 'lewis_car_wash');
if ($conn->connect_error) { die('Connection failed: ' . $conn->connect_error); } ?>