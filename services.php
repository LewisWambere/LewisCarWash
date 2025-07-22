<?php
include("db_connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Services - Lewis Car Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <a href="dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
  <h2>Service List</h2>
  <a href="add_service.php" class="btn btn-primary mb-3">+ Add Service</a>

  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Service Type</th>
        <th>Price (KSh)</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $result = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($result)) {
          echo "<tr>
                  <td>{$row['id']}</td>
                  <td>{$row['service_type']}</td>
                  <td>{$row['price']}</td>
                  <td>
                    <a href='edit_service.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
                    <a href='delete_service.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure?');\">Delete</a>
                  </td>
                </tr>";
        }
      ?>
    </tbody>
  </table>
</div>
</body>
</html>
