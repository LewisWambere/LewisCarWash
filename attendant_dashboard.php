<?php
session_start();
require 'db_connection.php';

// Redirect if not an attendant
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'attendant') {
    header('Location: login.php');
    exit;
}

// Handle transaction submission
$receipt = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_date = date("Y-m-d H:i:s");

    // Fetch service price
    $stmt = $conn->prepare("SELECT name, price FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service_result = $stmt->get_result();

    if ($service_result && $service_result->num_rows > 0) {
        $service = $service_result->fetch_assoc();
        $service_name = $service['name'];
        $price = floatval($service['price']);
        $balance = $amount_paid - $price;

        // Insert into transactions
        $insert = $conn->prepare("INSERT INTO transactions (customer_id, service_id, amount_paid, payment_date, amount) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("iidss", $customer_id, $service_id, $amount_paid, $payment_date, $price);
        $insert->execute();

        // Fetch customer name
        $cust = $conn->prepare("SELECT name FROM customers WHERE id = ?");
        $cust->bind_param("i", $customer_id);
        $cust->execute();
        $cust_result = $cust->get_result();
        $customer_name = $cust_result->fetch_assoc()['name'];

        $receipt = "
            <div class='receipt'>
                <h3>Receipt</h3>
                <p><strong>Customer:</strong> $customer_name</p>
                <p><strong>Service:</strong> $service_name</p>
                <p><strong>Price:</strong> KES " . number_format($price, 2) . "</p>
                <p><strong>Amount Paid:</strong> KES " . number_format($amount_paid, 2) . "</p>
                <p><strong>Balance:</strong> KES " . number_format($balance, 2) . "</p>
                <p><strong>Date:</strong> $payment_date</p>
            </div>
        ";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendant Dashboard - Lewis Car Wash</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/attendant_dashboard.css">
    <style>
        body {
            font-family: Arial;
            margin: 30px;
            background: #f9f9f9;
        }

        h1 {
            color: #333;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        a.logout {
            text-decoration: none;
            color: white;
            background-color: #d9534f;
            padding: 8px 15px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #aaa;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        form {
            background: #fff;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0px 0px 10px #ccc;
            border-radius: 8px;
        }

        .receipt {
            margin-top: 20px;
            background: #e9ffe9;
            padding: 15px;
            border-left: 5px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Lewis Car Wash</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <h2>Welcome, Attendant</h2>

    <!-- Available Services -->
    <h3>Available Services</h3>
    <table>
        <tr>
            <th>Service Type</th>
            <th>Price (KES)</th>
        </tr>
        <?php
        $service_query = $conn->query("SELECT service_type, price FROM services");
        while ($row = $service_query->fetch_assoc()):
        ?>
            <tr>
                <td><?= htmlspecialchars($row['service_type']) ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Record Transaction -->
    <form method="post">
        <h3>Record Transaction</h3>

        <label>Customer:</label>
        <select name="customer_id" required>
            <option value="">Select Customer</option>
            <?php
            $cust_result = $conn->query("SELECT id, name FROM customers");
            while ($cust = $cust_result->fetch_assoc()):
            ?>
                <option value="<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <br><br>

        <label>Service:</label>
        <select name="service_id" required>
            <option value="">Select Service</option>
            <?php
            $service_result = $conn->query("SELECT id, name FROM services");
            while ($svc = $service_result->fetch_assoc()):
            ?>
                <option value="<?= $svc['id'] ?>"><?= htmlspecialchars($svc['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <br><br>

        <label>Amount Paid (KES):</label>
        <input type="number" step="0.01" name="amount_paid" required>

        <br><br>

        <button type="submit">Submit Transaction</button>
    </form>

    <!-- Show receipt if exists -->
    <?php echo $receipt; ?>
</body>
</html>
