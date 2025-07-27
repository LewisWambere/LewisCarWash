<?php
include('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim whitespace and sanitize inputs
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $role = mysqli_real_escape_string($conn, trim($_POST['role']));
    
    // Additional fields for customer table
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    
    // Check if username already exists
    $check_query = "SELECT * FROM users WHERE username='$username'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "Username already exists! Please choose a different username.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert new user
            $user_query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
            
            if (mysqli_query($conn, $user_query)) {
                $user_id = mysqli_insert_id($conn);
                
                // If role is customer, also insert into customers table
                if ($role === 'customer') {
                    $customer_query = "INSERT INTO customers (user_id, name, email, phone, date_added) VALUES ('$user_id', '$full_name', '$email', '$phone', NOW())";
                    
                    if (!mysqli_query($conn, $customer_query)) {
                        throw new Exception("Error creating customer record: " . mysqli_error($conn));
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                $success = "Account created successfully! Redirecting to login...";
                // Redirect after 2 seconds
                header("refresh:2;url=login.php");
                
            } else {
                throw new Exception("Error creating user account: " . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Lewis Car Wash</title>
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

        /* Customer-specific fields styling */
        .customer-fields {
            display: none;
            animation: slideDown 0.3s ease-in-out;
        }

        .customer-fields.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 500px;
            }
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

                <!-- Signup Form -->
                <div class="auth-form">
                    <h2>Join Our Team</h2>
                    <form method="POST" action="signup.php" autocomplete="off">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" required class="form-control" placeholder="Choose a username">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" required class="form-control" placeholder="Create a secure password">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" required class="form-control">
                                <option value="">Select your role</option>
                                <option value="admin">Admin</option>
                                <option value="attendant">Attendant</option>
                                <option value="customer">Customer</option>
                            </select>
                        </div>
                        
                        <!-- Customer-specific fields (shown only when customer role is selected) -->
                        <div id="customer-fields" class="customer-fields">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter your full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter your phone number">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>
                    </form>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="auth-links">
                        <p>Already have an account? <a href="login.php">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Image Section -->
        <div class="image-section">
            <div class="image-content">
                <h3>Join Our <span class="highlight">Professional</span><br>Team</h3>
                <p>Be part of the premier car wash service team. Start your career in automotive care today.</p>
            </div>
        </div>
    </div>

    <script>
        // Show/hide customer-specific fields based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const customerFields = document.getElementById('customer-fields');
            const fullNameInput = document.getElementById('full_name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            
            if (this.value === 'customer') {
                customerFields.classList.add('show');
                // Make customer fields required
                fullNameInput.required = true;
                emailInput.required = true;
                phoneInput.required = true;
            } else {
                customerFields.classList.remove('show');
                // Remove required attribute for non-customer roles
                fullNameInput.required = false;
                emailInput.required = false;
                phoneInput.required = false;
                // Clear values
                fullNameInput.value = '';
                emailInput.value = '';
                phoneInput.value = '';
            }
        });

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

        // Password strength indicator (optional enhancement)
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            // You can add visual feedback here if desired
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
    </script>
</body>
</html>