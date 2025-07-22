<?php
session_start();

// Redirect if not logged in or not an attendant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'attendant') {
    header("Location: login.php");
    exit;
}

require 'db_connection.php';

// Get attendant info
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
    <title>Attendant Dashboard - Lewis Car Wash</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f1f1;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #17a2b8;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 22px;
        }

        a.logout {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .container {
            padding: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            margin: auto;
        }

        .welcome {
            font-size: 18px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<header>
    <h1>Lewis Car Wash - Attendant Dashboard</h1>
    <a href="logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <div class="card">
        <div class="welcome">
            Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!
        </div>
        <p>You are logged in as an <strong>Attendant</strong>.</p>
        <p>This dashboard will soon let you manage customers, view transactions, and more.</p>
    </div>
</div>

</body>
</html>
