<?php
session_start();
include("db_connection.php");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $service_id = $_POST['service_id'];
    $amount = $_POST['amount'];

    // Get service price
    $service_query = "SELECT price FROM services WHERE id = $service_id";
    $service_result = mysqli_query($conn, $service_query);
    $service_data = mysqli_fetch_assoc($service_result);
    $price = $service_data['price'];

    $amount_paid = $price * $amount;

    // Insert into transactions
    $insert_query = "
        INSERT INTO transactions (customer_id, service_id, amount, amount_paid)
        VALUES ($customer_id, $service_id, $amount, $amount_paid)
    ";

    if (mysqli_query($conn, $insert_query)) {
        header("Location: transactions.php?success=1");
        exit();
    } else {
        $error = "Failed to add transaction.";
    }
}

// Get customers and services for dropdowns
$customers = mysqli_query($conn, "SELECT id, name FROM customers");
$services = mysqli_query($conn, "SELECT id, service_type FROM services");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Add New Transaction</h2>
    <a href="transactions.php" class="btn btn-secondary mb-3">← Back to Transactions</a>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="customer_id" class="form-label">Customer</label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select customer</option>
                <?php while ($row = mysqli_fetch_assoc($customers)): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="service_id" class="form-label">Service</label>
            <select name="service_id" class="form-select" required>
                <option value="">Select service</option>
                <?php while ($row = mysqli_fetch_assoc($services)): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['service_type']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Number of Times (Amount)</label>
            <input type="number" name="amount" class="form-control" required min="1">
        </div>

        <button type="submit" class="btn btn-primary">Add Transaction</button>
    </form>
</div>

</body>
</html>
