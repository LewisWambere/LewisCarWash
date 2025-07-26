<?php
session_start();
include('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim whitespace and sanitize inputs
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $role = mysqli_real_escape_string($conn, trim($_POST['role']));

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password' AND role='$role'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        if ($role == 'admin') {
            header("Location: dashboard.php");
        } elseif ($role == 'attendant') {
            header("Location: attendant_dashboard.php");
        } else {
            header("Location: customer_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid login credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Split Screen Layout */
        .auth-container {
            display: flex;
            min-height: 100vh;
        }

        /* Left Side - Form Section */
        .form-section {
            flex: 1;
            background-color: #ffffff;
            color: #333333;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            max-width: 50%;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        /* Logo Styling */
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .logo .tagline {
            font-size: 0.9rem;
            color: #666666;
            font-weight: 300;
        }

        /* Form Styling */
        .auth-form h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #1a1a1a;
            font-weight: 600;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333333;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control:hover {
            border-color: #cccccc;
        }

        /* Button Styling */
        .btn-primary {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
            background: linear-gradient(135deg, #ffed4a 0%, #ffd700 100%);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }

        /* Alert Styling */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fff4;
            color: #38a169;
            border: 1px solid #9ae6b4;
        }

        /* Links and Text */
        .auth-links {
            text-align: center;
            margin-top: 2rem;
        }

        .auth-links p {
            color: #666666;
            font-size: 0.95rem;
        }

        .auth-links a {
            color: #ffd700;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-links a:hover {
            color: #e6c200;
            text-decoration: underline;
        }

        /* Right Side - Image Section */
        .image-section {
            flex: 1;
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.8) 0%, rgba(0, 0, 0, 0.6) 100%),
                        url('images/car-wash-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            max-width: 50%;
        }

        .image-content {
            text-align: center;
            padding: 2rem;
            z-index: 2;
        }

        .image-content h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffffff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            line-height: 1.2;
        }

        .image-content .highlight {
            color: #ffd700;
        }

        .image-content p {
            font-size: 1.2rem;
            color: #e0e0e0;
            font-weight: 300;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            line-height: 1.5;
        }

        /* Admin PIN Field Animation */
        #admin_pin_field {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 0;
        }

        #admin_pin_field.show {
            opacity: 1;
            max-height: 100px;
            margin-bottom: 1.5rem;
        }

        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            
            .form-section, .image-section {
                max-width: 100%;
                flex: none;
            }
            
            .form-section {
                order: 2;
                padding: 1rem;
            }
            
            .image-section {
                order: 1;
                min-height: 40vh;
                background-attachment: scroll;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .image-content h3 {
                font-size: 2rem;
            }
            
            .image-content p {
                font-size: 1rem;
            }
            
            .form-wrapper {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .form-section {
                padding: 0.5rem;
            }
            
            .form-wrapper {
                padding: 0.5rem;
            }
            
            .logo h1 {
                font-size: 1.8rem;
            }
            
            .auth-form h2 {
                font-size: 1.5rem;
            }
            
            .image-content h3 {
                font-size: 1.5rem;
            }
            
            .image-section {
                min-height: 30vh;
            }
        }

        /* Loading Animation */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary.loading {
            position: relative;
            color: transparent;
        }

        .btn-primary.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #1a1a1a;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Focus States for Accessibility */
        .form-control:focus,
        .btn-primary:focus,
        a:focus {
            outline: 2px solid #ffd700;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Left Side - Form Section -->
        <div class="form-section">
            <div class="form-wrapper">
                <!-- Logo -->
                <div class="logo">
                    <h1><i class="fas fa-car-wash"></i> Lewis</h1>
                    <p class="tagline">Premium Car Care Services</p>
                </div>

                <!-- Login Form -->
                <div class="auth-form">
                    <h2>Welcome Back</h2>
                    <form method="POST" action="login.php" autocomplete="off">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required class="form-control" placeholder="Enter your username">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required class="form-control" placeholder="Enter your password">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required class="form-control" onchange="toggleAdminPin()">
                                <option value="">Select your role</option>
                                <option value="admin">Admin</option>
                                <option value="attendant">Attendant</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        
                        <div id="admin_pin_field" class="form-group">
                            <label for="admin_pin">Admin PIN</label>
                            <input type="password" id="admin_pin" name="admin_pin" class="form-control" placeholder="Enter admin PIN">
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                    </form>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="auth-links">
                        <p>Don't have an account? <a href="signup.php">Create Account</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Image Section -->
        <div class="image-section">
            <div class="image-content">
                <h3>Premium <span class="highlight">Car Wash</span><br>Experience</h3>
                <p>Professional car care services with state-of-the-art equipment and eco-friendly products.</p>
            </div>
        </div>
    </div>

    <script>
        function toggleAdminPin() {
            var role = document.getElementById('role');
            var adminPinField = document.getElementById('admin_pin_field');
            
            if (role.value === 'admin') {
                adminPinField.classList.add('show');
                adminPinField.style.display = 'block';
            } else {
                adminPinField.classList.remove('show');
                setTimeout(() => {
                    if (!adminPinField.classList.contains('show')) {
                        adminPinField.style.display = 'none';
                    }
                }, 300);
            }
        }

        // Add loading state to button on form submit
        document.querySelector('form').addEventListener('submit', function() {
            const button = document.querySelector('.btn-primary');
            button.classList.add('loading');
            button.disabled = true;
        });

        // Add smooth focus transitions
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>