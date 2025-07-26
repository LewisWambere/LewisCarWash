<?php
require_once 'db_connection.php';
session_start();

// FIXED: Check for correct session variable
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // FIXED: Store values in variables first before binding
                    $service_type = trim($_POST['service_type']);
                    $price = floatval($_POST['price']);
                    
                    $stmt = mysqli_prepare($conn, "INSERT INTO services (service_type, price) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt, "sd", $service_type, $price);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $message = "Service added successfully!";
                    $message_type = "success";
                    break;

                case 'update':
                    // FIXED: Store values in variables first before binding
                    $service_type = trim($_POST['service_type']);
                    $price = floatval($_POST['price']);
                    $service_id = intval($_POST['service_id']);
                    
                    $stmt = mysqli_prepare($conn, "UPDATE services SET service_type = ?, price = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "sdi", $service_type, $price, $service_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $message = "Service updated successfully!";
                    $message_type = "success";
                    break;

                case 'delete':
                    // FIXED: Store value in variable first before binding
                    $service_id = intval($_POST['service_id']);
                    
                    $stmt = mysqli_prepare($conn, "DELETE FROM services WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "i", $service_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $message = "Service deleted successfully!";
                    $message_type = "success";
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// FIXED: Fetch services using your actual column names
$result = mysqli_query($conn, "SELECT * FROM services ORDER BY service_type ASC");
if (!$result) {
    $message = "Error fetching services: " . mysqli_error($conn);
    $message_type = "error";
}

// Get service for editing if edit_id is provided
$edit_service = null;
if (isset($_GET['edit_id'])) {
    $edit_stmt = mysqli_prepare($conn, "SELECT * FROM services WHERE id = ?");
    mysqli_stmt_bind_param($edit_stmt, "i", intval($_GET['edit_id']));
    mysqli_stmt_execute($edit_stmt);
    $edit_result = mysqli_stmt_get_result($edit_stmt);
    $edit_service = mysqli_fetch_assoc($edit_result);
    mysqli_stmt_close($edit_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Lewis Car Wash Admin</title>
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
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
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
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
            color: #1a1a1a;
            text-decoration: none;
        }

        .services-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            font-weight: 500;
            border: 2px solid;
            animation: slideInDown 0.5s ease;
        }
        
        .alert.success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1) 0%, rgba(46, 204, 113, 0.1) 100%);
            color: #2ecc71;
            border-color: #27ae60;
        }
        
        .alert.error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: #e74c3c;
            border-color: #c0392b;
        }
        
        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2.5rem;
            border-radius: 16px;
            border: 2px solid #333;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .form-title {
            color: #ffd700;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e0e0e0;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #2d2d2d;
            color: #ffffff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .services-table {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 16px;
            border: 2px solid #333;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .table-title {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: #ffd700;
            padding: 1.5rem 2rem;
            margin: 0;
            font-size: 1.5rem;
            border-bottom: 2px solid #ffd700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            color: #ffd700;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        td {
            color: #e0e0e0;
        }
        
        tr:hover td {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .price {
            font-weight: 600;
            color: #ffd700;
            font-size: 1.1rem;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .no-services {
            text-align: center;
            padding: 3rem;
            color: #b0b0b0;
            font-style: italic;
            font-size: 1.1rem;
        }

        .no-services i {
            font-size: 3rem;
            color: #444;
            margin-bottom: 1rem;
            display: block;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .services-container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .page-header h1 {
                font-size: 2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .actions {
                flex-direction: column;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 1rem 0.5rem;
            }

            .form-container {
                padding: 1.5rem;
            }
        }

        /* Animation */
        .form-container, .services-table {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .services-table {
            animation-delay: 0.2s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="services-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-car-side"></i> Manage Services</h1>
            <p>Add, edit, and manage your car wash services</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Service Form -->
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-<?= $edit_service ? 'edit' : 'plus-circle' ?>"></i>
                <?= $edit_service ? 'Edit Service' : 'Add New Service' ?>
            </h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $edit_service ? 'update' : 'add' ?>">
                <?php if ($edit_service): ?>
                    <input type="hidden" name="service_id" value="<?= $edit_service['id'] ?>">
                <?php endif; ?>
                
                <!-- FIXED: Changed from service_name to service_type -->
                <div class="form-group">
                    <label for="service_type">Service Type *</label>
                    <input type="text" id="service_type" name="service_type" 
                           value="<?= $edit_service ? htmlspecialchars($edit_service['service_type']) : '' ?>" 
                           required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="price">Price (KES) *</label>
                    <input type="number" id="price" name="price" 
                           value="<?= $edit_service ? $edit_service['price'] : '' ?>" 
                           required min="0" step="0.01" placeholder="0.00">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?= $edit_service ? 'save' : 'plus' ?>"></i>
                        <?= $edit_service ? 'Update Service' : 'Add Service' ?>
                    </button>
                    <?php if ($edit_service): ?>
                        <a href="services.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Services Table -->
        <div class="services-table">
            <h2 class="table-title">
                <i class="fas fa-list"></i> Current Services
            </h2>
            
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <!-- FIXED: Changed column headers to match your database -->
                            <th>Service Type</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <!-- FIXED: Using service_type instead of name -->
                                <td><strong><?= htmlspecialchars($row['service_type']) ?></strong></td>
                                <td class="price">KES <?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="services.php?edit_id=<?= $row['id'] ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this service?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="service_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-services">
                    <i class="fas fa-car-side"></i>
                    <p>No services found. Add your first service using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert.success');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const serviceType = document.getElementById('service_type').value.trim();
            const price = document.getElementById('price').value;
            
            if (!serviceType) {
                alert('Please enter a service type.');
                e.preventDefault();
                return;
            }
            
            if (!price || parseFloat(price) < 0) {
                alert('Please enter a valid price.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>