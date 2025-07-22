<?php
session_start();
include("db_connection.php");

// Fetch summary counts
$totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers"))['total'];
$totalTransactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transactions"))['total'];
$totalServices = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM services"))['total'];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Lewis Car Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Lewis Car Wash</a>
  </div>
</nav>

<div class="container mt-4">
  <h2 class="mb-4">Dashboard Overview</h2>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="card text-white bg-primary shadow">
        <div class="card-body">
          <h5 class="card-title">Total Customers</h5>
          <p class="card-text fs-4 fw-bold"><?= $totalCustomers ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-white bg-success shadow">
        <div class="card-body">
          <h5 class="card-title">Total Services</h5>
          <p class="card-text fs-4 fw-bold"><?= $totalServices ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-white bg-warning shadow">
        <div class="card-body">
          <h5 class="card-title">Total Transactions</h5>
          <p class="card-text fs-4 fw-bold"><?= $totalTransactions ?></p>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card text-white bg-danger shadow">
        <div class="card-body">
          <h5 class="card-title">Total Revenue</h5>
          <p class="card-text fs-4 fw-bold">Ksh <?= number_format($totalRevenue) ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-3">
      <a href="customers.php" class="btn btn-outline-primary w-100 py-3">Manage Customers</a>
    </div>
    <div class="col-md-3">
      <a href="services.php" class="btn btn-outline-success w-100 py-3">Manage Services</a>
    </div>
    <div class="col-md-3">
      <a href="transactions.php" class="btn btn-outline-warning w-100 py-3">View Transactions</a>
    </div>
    <div class="col-md-3">
      <a href="#" class="btn btn-outline-secondary w-100 py-3 disabled">Reports (Coming Soon)</a>
    </div>
  </div>
</div>

</body>
</html>
