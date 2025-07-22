<?php
session_start();

// Redirect to login if not logged in or not a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

require 'db_connection.php';

// Get customer info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard - Lewis Car Wash</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2f3;
            margin: 0;
            padding: 0;
        }

        header {
            background: #007bff;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 20px;
        }

        .container {
            padding: 30px;
        }

        .welcome {
            font-size: 18px;
            margin-bottom: 20px;
        }

        a.logout {
            color: white;
            text-decoration: none;
            background: #dc3545;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: auto;
        }
    </style>
</head>
<body>

<header>
    <h1>Lewis Car Wash - Customer Dashboard</h1>
    <a href="logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <div class="card">
        <div class="welcome">
            Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!
        </div>
        <p>Here you can view your services, payment history, and manage your profile. (Coming soon!)</p>
    </div>
</div>

</body>
</html>
