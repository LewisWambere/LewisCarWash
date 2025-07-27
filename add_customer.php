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
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $car_plate = mysqli_real_escape_string($conn, $_POST['car_plate']);
    $car_type = mysqli_real_escape_string($conn, $_POST['car_type']);
    $service_type = mysqli_real_escape_string($conn, $_POST['service_type']);
    $service_date = $_POST['service_date'];
    
    // Generate username from name (remove spaces, convert to lowercase)
    $username = strtolower(str_replace(' ', '', $name));
    $original_username = $username;
    $counter = 1;
    
    // Check if username exists and modify if needed
    while (true) {
        $check_username = "SELECT * FROM users WHERE username='$username'";
        $check_result = mysqli_query($conn, $check_username);
        if (mysqli_num_rows($check_result) == 0) {
            break;
        }
        $username = $original_username . $counter;
        $counter++;
    }
    
    // Default password (can be changed later)
    $default_password = 'customer123'; // You might want to generate a random password
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, insert into users table
        $user_sql = "INSERT INTO users (username, password, role, name, email, phone) 
                     VALUES ('$username', '$default_password', 'customer', '$name', '$email', '$phone')";
        
        if (!mysqli_query($conn, $user_sql)) {
            throw new Exception("Error creating user account: " . mysqli_error($conn));
        }
        
        $user_id = mysqli_insert_id($conn);
        
        // Then insert into customers table (for backward compatibility)
        $customer_sql = "INSERT INTO customers (name, phone, car_plate, car_type, service_type, service_date, user_id)
                        VALUES ('$name', '$phone', '$car_plate', '$car_type', '$service_type', '$service_date', '$user_id')";
        
        if (!mysqli_query($conn, $customer_sql)) {
            throw new Exception("Error creating customer record: " . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $msg = "Customer record added successfully! Username: $username (Password: $default_password)";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $msg = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-bottom: 3px solid #ffd700;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-section h1 {
            font-size: 2rem;
            color: #ffd700;
            font-weight: bold;
        }

        .back-btn {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .main-content {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-title p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }

        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 3rem;
            border-radius: 16px;
            border: 2px solid #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .form-title {
            color: #ffd700;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #ffd700;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 8px;
            background: #2d2d2d;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            background-color: #333;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control:hover {
            border-color: #666;
        }

        select.form-control {
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffed4a 0%, #ffd700 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .msg {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .msg.success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .msg.error {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .form-info {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            color: #3498db;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .form-info i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            
            .form-container {
                padding: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem;
            }
            
            .page-title h2 {
                font-size: 1.8rem;
            }
        }

        .required {
            color: #e74c3c;
        }

        .form-control.invalid {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }

        .form-control.valid {
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <?php
                $dashboard_url = 'dashboard.php';
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'attendant') {
                        $dashboard_url = 'attendant_dashboard.php';
                    } elseif ($_SESSION['role'] === 'customer') {
                        $dashboard_url = 'customer_dashboard.php';
                    }
                }
                ?>
                <a href="<?= $dashboard_url ?>" style="text-decoration: none;">
                    <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
                </a>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-title">
            <h2>Add New Customer</h2>
            <p>Register a new customer and create their first service record</p>
        </div>

        <div class="form-container">
            <h3 class="form-title">
                <i class="fas fa-user-plus"></i> Customer Registration
            </h3>

            <div class="form-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> This will create both a customer record and a user account. 
                The customer will be able to log in using the generated username and default password.
            </div>

            <form method="POST" class="add-customer-form" autocomplete="off">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required class="form-control" placeholder="Enter customer's full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required class="form-control" placeholder="e.g., 0712345678">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="customer@example.com (optional)">
                    </div>
                    
                    <div class="form-group">
                        <label for="car_plate">Car Registration <span class="required">*</span></label>
                        <input type="text" id="car_plate" name="car_plate" required class="form-control" placeholder="e.g., KCA 123A">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="car_type">Vehicle Type <span class="required">*</span></label>
                        <input type="text" id="car_type" name="car_type" required class="form-control" placeholder="e.g., Toyota Camry, Honda Civic">
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Service Type <span class="required">*</span></label>
                        <select id="service_type" name="service_type" required class="form-control">
                            <option value="">-- Select Service --</option>
                            <option value="Basic Wash">Basic Wash</option>
                            <option value="Premium Wash">Premium Wash</option>
                            <option value="Wax & Polish">Wax & Polish</option>
                            <option value="Interior Cleaning">Interior Cleaning</option>
                            <option value="Full Service">Full Service</option>
                            <option value="Vacuum Only">Vacuum Only</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="service_date">Service Date <span class="required">*</span></label>
                    <input type="date" id="service_date" name="service_date" required class="form-control">
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Customer Record
                </button>
            </form>

            <?php if ($msg): ?>
                <div class="msg <?= strpos($msg, 'Error') !== false ? 'error' : 'success' ?>" role="status">
                    <?= $msg ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Set default service date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('service_date').value = today;
        });

        // Form validation
        document.querySelector('.add-customer-form').addEventListener('submit', function(e) {
            const requiredFields = ['name', 'phone', 'car_plate', 'car_type', 'service_type', 'service_date'];
            let isValid = true;

            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.classList.add('invalid');
                    isValid = false;
                } else {
                    field.classList.remove('invalid');
                    field.classList.add('valid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });

        // Real-time validation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required')) {
                    if (this.value.trim()) {
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                    } else {
                        this.classList.add('invalid');
                        this.classList.remove('valid');
                    }
                }
            });

            input.addEventListener('input', function() {
                this.classList.remove('invalid', 'valid');
            });
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substring(0, 10);
            e.target.value = value;
        });

        // Car plate formatting (uppercase)
        document.getElementById('car_plate').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });

        // Name formatting (proper case)
        document.getElementById('name').addEventListener('blur', function(e) {
            const words = e.target.value.toLowerCase().split(' ');
            const properCase = words.map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
            e.target.value = properCase;
        });
    </script>
</body>
</html>