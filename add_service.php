<?php
include("db_connection.php");
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token (you should implement this)
    // if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    //     $error = "Invalid request. Please try again.";
    // } else {
    
    $serviceType = trim($_POST['service_type'] ?? '');
    $price = $_POST['price'] ?? '';

    // Server-side validation
    if (empty($serviceType)) {
        $error = "Service name is required.";
    } elseif (strlen($serviceType) < 3) {
        $error = "Service name must be at least 3 characters long.";
    } elseif (strlen($serviceType) > 100) {
        $error = "Service name must be less than 100 characters.";
    } elseif (!is_numeric($price)) {
        $error = "Price must be a valid number.";
    } elseif ($price < 0) {
        $error = "Price cannot be negative.";
    } elseif ($price > 100000) {
        $error = "Price cannot exceed KSh 100,000.";
    } else {
        $price = floatval($price);
        
        // Check if service already exists
        $checkQuery = "SELECT id FROM services WHERE name = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "s", $serviceType);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "A service with this name already exists.";
        } else {
            // Use prepared statement to prevent SQL injection
            $query = "INSERT INTO services (name, price, created_at) VALUES (?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $query);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sd", $serviceType, $price);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Service added successfully!";
                    // Clear form values on success
                    $serviceType = '';
                    $price = '';
                } else {
                    $error = "Error adding service. Please try again.";
                }
                
                mysqli_stmt_close($stmt);
            } else {
                $error = "Database error. Please try again.";
            }
        }
        mysqli_stmt_close($checkStmt);
    }
}

// Generate CSRF token (implement this properly)
// if (!isset($_SESSION['csrf_token'])) {
//     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - Lewis Car Wash</title>
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
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        /* Header */
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-section h1 {
            font-size: 2rem;
            color: #ffd700;
            font-weight: bold;
        }

        .back-btn {
            background: linear-gradient(135deg, #333 0%, #555 100%);
            color: #ffd700;
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
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
            color: #ffd700;
            text-decoration: none;
        }

        /* Main Content */
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

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            border: 1px solid #f44336;
        }

        .alert-success {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            border: 1px solid #4caf50;
        }

        /* Form Section */
        .form-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 3rem;
            border-radius: 16px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .form-title {
            font-size: 1.5rem;
            color: #ffd700;
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            color: #e0e0e0;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem;
            border: 2px solid #555;
            border-radius: 8px;
            background: #333;
            color: #fff;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control::placeholder {
            color: #999;
        }

        .form-control.error {
            border-color: #f44336;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1.2rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #666 0%, #777 100%);
            color: white;
            text-decoration: none;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 102, 102, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Service Preview */
        .service-preview {
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid #ffd700;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
            text-align: center;
            display: none;
        }

        .service-preview.show {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        .service-preview h4 {
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .service-preview .preview-name {
            font-size: 1.2rem;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .service-preview .preview-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4caf50;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .main-content {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .page-title h2 {
                font-size: 2rem;
            }

            .form-section {
                padding: 2rem 1.5rem;
            }

            .btn-group {
                flex-direction: column;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-section {
            animation: fadeInUp 0.6s ease;
        }

        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
            </div>
            <a href="services.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Title -->
        <div class="page-title">
            <h2>Add New Service</h2>
            <p>Create a new car wash service for your customers</p>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Add Service Form -->
        <div class="form-section">
            <h3 class="form-title">
                <i class="fas fa-plus-circle"></i>
                Service Details
            </h3>
            
            <form method="POST" action="" id="serviceForm" novalidate>
                <!-- CSRF Token (uncomment when implementing) -->
                <!-- <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> -->
                
                <div class="form-group">
                    <label for="service_type">Service Name *</label>
                    <input type="text" 
                           class="form-control" 
                           id="service_type" 
                           name="service_type" 
                           placeholder="e.g., Basic Wash, Premium Detail, Interior Cleaning"
                           value="<?php echo htmlspecialchars($serviceType ?? ''); ?>"
                           required
                           maxlength="100"
                           oninput="updatePreview(); validateField(this)">
                </div>
                
                <div class="form-group">
                    <label for="price">Price (KSh) *</label>
                    <input type="number" 
                           step="0.01" 
                           min="0"
                           max="100000"
                           class="form-control" 
                           id="price" 
                           name="price" 
                           placeholder="Enter service price"
                           value="<?php echo htmlspecialchars($price ?? ''); ?>"
                           required
                           oninput="updatePreview(); validateField(this)">
                </div>

                <!-- Service Preview -->
                <div class="service-preview" id="servicePreview">
                    <h4><i class="fas fa-eye"></i> Service Preview</h4>
                    <div class="preview-name" id="previewName">Service Name</div>
                    <div class="preview-price" id="previewPrice">KSh 0.00</div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Add Service
                    </button>
                    <a href="services.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let formValid = false;

        function updatePreview() {
            const serviceName = document.getElementById('service_type').value;
            const price = document.getElementById('price').value;
            const preview = document.getElementById('servicePreview');
            const previewName = document.getElementById('previewName');
            const previewPrice = document.getElementById('previewPrice');
            
            if (serviceName || price) {
                preview.classList.add('show');
                previewName.textContent = serviceName || 'Service Name';
                previewPrice.textContent = 'KSh ' + (price ? parseFloat(price).toFixed(2) : '0.00');
            } else {
                preview.classList.remove('show');
            }
        }

        function validateField(field) {
            const value = field.value.trim();
            field.classList.remove('error');
            
            if (field.id === 'service_type') {
                if (value.length > 0 && value.length < 3) {
                    field.classList.add('error');
                    return false;
                }
            } else if (field.id === 'price') {
                const price = parseFloat(value);
                if (value && (isNaN(price) || price < 0 || price > 100000)) {
                    field.classList.add('error');
                    return false;
                }
            }
            
            return true;
        }

        function validateForm() {
            const serviceName = document.getElementById('service_type').value.trim();
            const price = document.getElementById('price').value;
            const submitBtn = document.getElementById('submitBtn');
            
            let isValid = true;
            
            // Validate service name
            if (serviceName.length < 3) {
                isValid = false;
            }
            
            // Validate price
            const priceNum = parseFloat(price);
            if (!price || isNaN(priceNum) || priceNum < 0 || priceNum > 100000) {
                isValid = false;
            }
            
            formValid = isValid;
            submitBtn.disabled = !isValid;
            
            return isValid;
        }

        // Form validation and submission
        document.getElementById('serviceForm').addEventListener('submit', function(e) {
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            
            if (!validateForm()) {
                e.preventDefault();
                
                const serviceName = document.getElementById('service_type').value.trim();
                const price = parseFloat(document.getElementById('price').value);
                
                if (serviceName.length === 0) {
                    alert('Service name is required.');
                } else if (serviceName.length < 3) {
                    alert('Service name must be at least 3 characters long.');
                } else if (!document.getElementById('price').value) {
                    alert('Price is required.');
                } else if (isNaN(price) || price < 0) {
                    alert('Price must be a positive number.');
                } else if (price > 100000) {
                    alert('Price cannot exceed KSh 100,000.');
                }
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Service...';
            form.classList.add('loading');
        });

        // Real-time validation
        document.getElementById('service_type').addEventListener('input', function() {
            validateField(this);
            validateForm();
        });

        document.getElementById('price').addEventListener('input', function() {
            validateField(this);
            validateForm();
        });

        // Auto-focus on service name field
        window.addEventListener('load', function() {
            document.getElementById('service_type').focus();
            validateForm(); // Initial validation
        });

        // Initialize preview if there are values (after form submission with errors)
        updatePreview();
    </script>
</body>
</html>