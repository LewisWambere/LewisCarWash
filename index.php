<?php
session_start();
include("db/connection.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lewis Car Wash Dashboard</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            padding: 40px;
        }
        .container {
            background: white;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #333;
        }
        .role {
            font-size: 14px;
            color: #888;
        }
        .actions a {
            display: block;
            background: #007BFF;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 15px auto;
            border-radius: 5px;
            width: 70%;
        }
        .actions a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
    <p class="role">Role: <?= htmlspecialchars($role) ?></p>

    <div class="actions">
        <a href="add_customer.php">➕ Add Customer</a>
        <a href="view_customers.php">📄 View Customers</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

</body>
</html>
