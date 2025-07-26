<?php
require_once('db/db_connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $amount_paid = $_POST['amount_paid'];

    // Get the original amount from services table
    $query = "SELECT amount FROM services WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($original_amount);
    $stmt->fetch();
    $stmt->close();

    $query = "INSERT INTO transactions (customer_id, service_id, amount_paid, amount, payment_date) 
              VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iidd", $customer_id, $service_id, $amount_paid, $original_amount);

    if ($stmt->execute()) {
        $message = "Transaction added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Get customers and services for dropdowns
$customers = $conn->query("SELECT id, name FROM customers");
$services = $conn->query("SELECT id, service_name FROM services");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Transaction</title>
    <link rel="stylesheet" href="css/add_transaction.css">
</head>
<body>
    <div class="container">
        <h2>Add New Transaction</h2>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="add_transaction.php">
            <div class="form-group">
                <label for="customer_id">Select Customer:</label>
                <select name="customer_id" required>
                    <option value="">-- Choose --</option>
                    <?php while ($row = $customers->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="service_id">Select Service:</label>
                <select name="service_id" required>
                    <option value="">-- Choose --</option>
                    <?php while ($row = $services->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['service_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount_paid">Amount Paid:</label>
                <input type="number" name="amount_paid" step="0.01" required>
            </div>

            <button type="submit">Add Transaction</button>
        </form>
    </div>
</body>
</html>
