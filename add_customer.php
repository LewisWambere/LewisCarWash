<?php
session_start();
include("db_connection.php");
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $car_plate = mysqli_real_escape_string($conn, $_POST['car_plate']);
    $car_type = mysqli_real_escape_string($conn, $_POST['car_type']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $service_date = $_POST['service_date'];
    $sql = "INSERT INTO customers (name, phone, car_plate, car_type, service_type, service_date)
            VALUES ('$name', '$phone', '$car_plate', '$car_type', '$service_type', '$service_date')";
    if (mysqli_query($conn, $sql)) {
        $msg = "Customer record added successfully!";
    } else {
        $msg = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Lewis Car Wash</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/add_customer.css">
</head>
<body>
    <main class="add-customer-main">
        <section class="add-customer-section">
            <div class="form-box add-customer-form-box">
                <a href="dashboard.php" class="btn btn-secondary mb-3">&larr; Back to Dashboard</a>
                <h2 class="add-customer-title">Add Customer Record</h2>
                <form method="POST" class="add-customer-form" autocomplete="off">
                    <div class="form-group">
                        <label for="name">Customer Name:</label>
                        <input type="text" id="name" name="name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="text" id="phone" name="phone" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="car_plate">Car Plate:</label>
                        <input type="text" id="car_plate" name="car_plate" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="car_type">Car Type:</label>
                        <input type="text" id="car_type" name="car_type" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="service_type">Service Type:</label>
                        <select id="service_type" name="service_type" required class="form-control">
                            <option value="">-- Select Service --</option>
                            <option value="Wash">Wash</option>
                            <option value="Wax">Wax</option>
                            <option value="Vacuum">Vacuum</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="service_date">Service Date:</label>
                        <input type="date" id="service_date" name="service_date" required class="form-control">
                    </div>
                    <input type="submit" value="Add Record" class="btn btn-success">
                </form>
                <?php if ($msg) echo "<p class='msg' role='status'>$msg</p>"; ?>
            </div>
        </section>
    </main>
    <script src="js/script.js"></script>
</body>
</html>
