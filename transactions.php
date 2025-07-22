<?php
session_start();
include("db_connection.php"); // Adjust if needed

// Fetch all transactions
$query = "
    SELECT 
        t.id,
        c.name AS customer_name,
        s.service_type,
        s.price,
        t.amount,
        t.amount_paid,
        t.payment_date
    FROM transactions t
    LEFT JOIN customers c ON t.customer_id = c.id
    LEFT JOIN services s ON t.service_id = s.id
    ORDER BY t.payment_date DESC
";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Transactions - Lewis Car Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
  <h2 class="mb-4">Transactions</h2>

  <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
  <a href="add_transaction.php" class="btn btn-success mb-3">+ Add New Transaction</a>

  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Service Price</th>
        <th>Amount</th>
        <th>Amount Paid</th>
        <th>Payment Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['service_type']) ?></td>
            <td>KES <?= number_format($row['price'], 2) ?></td>
            <td><?= $row['amount'] ?></td>
            <td>KES <?= number_format($row['amount_paid'], 2) ?></td>
            <td><?= $row['payment_date'] ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="7" class="text-center">No transactions found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
