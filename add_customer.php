<?php
session_start();
include("db/connection.php");

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $car_type = mysqli_real_escape_string($conn, $_POST['car_type']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $service_date = $_POST['service_date'];

    $sql = "INSERT INTO customers (name, phone, car_type, service_type, service_date)
            VALUES ('$name', '$phone', '$car_type', '$service_type', '$service_date')";

    if (mysqli_query($conn, $sql)) {
        $msg = "Customer record added successfully!";
    } else {
        $msg = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Customer - Lewis Car Wash</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 40px; }
        .form-box { background: #fff; padding: 20px; max-width: 500px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        h2 { text-align: center; color: #333; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
        input[type=submit] { background: #28a745; color: white; border: none; cursor: pointer; }
        input[type=submit]:hover { background: #218838; }
        .msg { text-align: center; color: green; font-weight: bold; }
    </style>
</head>
<body>
<div class="form-box">
    <a href="dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
    <h2>Add Customer Record</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Customer Name" required />
        <input type="text" name="phone" placeholder="Phone Number" required />
        <input type="text" name="car_type" placeholder="Car Type" required />
        <select name="service_type" required>
            <option value="">-- Select Service --</option>
            <option value="Wash">Wash</option>
            <option value="Wax">Wax</option>
            <option value="Vacuum">Vacuum</option>
        </select>
        <input type="date" name="service_date" required />
        <input type="submit" value="Add Record" />
    </form>
    <?php if ($msg) echo "<p class='msg'>$msg</p>"; ?>
</div>
</body>
</html>
