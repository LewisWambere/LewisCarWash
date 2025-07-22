<?php
session_start();
include("db_connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customers - Lewis Car Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
  <a href="dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
  <h2>Customer List</h2>
  
  
  <a href="new_customer.php" class="btn btn-primary mb-3">+ Add Customer</a>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Car Plate</th>
        <th>Car Type</th>
        <th>Service Type</th>
        <th>Date Added</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT * FROM customers ORDER BY id DESC";
      $result = mysqli_query($conn, $sql);

      while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['car_plate']}</td>
                <td>{$row['car_type']}</td>
                <td>{$row['service_type']}</td>
                <td>{$row['date_added']}</td>
                <td>
                  <a href='edit_customer.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
                  <a href='delete_customer.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure?');\">Delete</a>
                </td>
              </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

</body>
</html>
