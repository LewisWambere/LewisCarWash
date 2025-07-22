<?php
include("db_connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serviceType = mysqli_real_escape_string($conn, $_POST['service_type']);
    $price = floatval($_POST['price']);

    if (!empty($serviceType) && $price >= 0) {
        $query = "INSERT INTO services (service_type, price) VALUES ('$serviceType', '$price')";
        if (mysqli_query($conn, $query)) {
            header("Location: services.php");
            exit;
        } else {
            $error = "Error adding service: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Service - Lewis Car Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <a href="dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
  <h2>Add New Service</h2>
  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <div class="mb-3">
      <label for="service_type" class="form-label">Service Type</label>
      <input type="text" class="form-control" id="service_type" name="service_type" required>
    </div>
    <div class="mb-3">
      <label for="price" class="form-label">Price (KSh)</label>
      <input type="number" step="0.01" class="form-control" id="price" name="price" required>
    </div>
    <button type="submit" class="btn btn-success">Add Service</button>
    <a href="services.php" class="btn btn-secondary">Back</a>
  </form>
</div>
</body>
</html>
