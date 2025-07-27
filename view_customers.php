<?php
require_once 'db_connection.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// ===== HELPER FUNCTIONS =====
function updateCustomer($post, $conn) {
    $customer_id = intval($post['customer_id']);
    $name = trim($post['name']);
    $phone = trim($post['phone']);
    $email = trim($post['email']);
    $car_plate = trim($post['car_plate']);
    $car_type = trim($post['car_type']);
    $service_type = trim($post['service_type']);
    $service_date = $post['service_date'];
    
    $stmt = mysqli_prepare($conn, 
        "UPDATE customers SET name = ?, phone = ?, email = ?, car_plate = ?, 
         car_type = ?, service_type = ?, service_date = ? WHERE id = ?"
    );
    
    mysqli_stmt_bind_param($stmt, "sssssssi", 
        $name, $phone, $email, $car_plate, 
        $car_type, $service_type, $service_date, $customer_id
    );
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return ['message' => 'Customer updated successfully!', 'type' => 'success'];
}

function deleteCustomer($post, $conn) {
    $customer_id = intval($post['customer_id']);
    
    // Get user_id first
    $user_query = mysqli_prepare($conn, "SELECT user_id FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($user_query, "i", $customer_id);
    mysqli_stmt_execute($user_query);
    $user_result = mysqli_stmt_get_result($user_query);
    $user_row = mysqli_fetch_assoc($user_result);
    $user_id = $user_row['user_id'];
    mysqli_stmt_close($user_query);
    
    // Delete customer
    $customer_stmt = mysqli_prepare($conn, "DELETE FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($customer_stmt, "i", $customer_id);
    mysqli_stmt_execute($customer_stmt);
    mysqli_stmt_close($customer_stmt);
    
    // Delete user account
    if ($user_id) {
        $user_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
        mysqli_stmt_execute($user_stmt);
        mysqli_stmt_close($user_stmt);
    }
    
    return ['message' => 'Customer deleted successfully!', 'type' => 'success'];
}

function getCustomerStats($conn) {
    $stats = [];
    
    // Total customers
    $total_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM customers");
    $stats['total'] = mysqli_fetch_assoc($total_query)['count'];
    
    // Today
    $today_query = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM customers WHERE DATE(date_added) = CURDATE()"
    );
    $stats['today'] = mysqli_fetch_assoc($today_query)['count'];
    
    // This week
    $week_query = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM customers 
         WHERE WEEK(date_added) = WEEK(NOW()) AND YEAR(date_added) = YEAR(NOW())"
    );
    $stats['week'] = mysqli_fetch_assoc($week_query)['count'];
    
    // This month
    $month_query = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM customers 
         WHERE MONTH(date_added) = MONTH(NOW()) AND YEAR(date_added) = YEAR(NOW())"
    );
    $stats['month'] = mysqli_fetch_assoc($month_query)['count'];
    
    return $stats;
}

// ===== HANDLE FORM SUBMISSIONS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update':
                    $result = updateCustomer($_POST, $conn);
                    $message = $result['message'];
                    $message_type = $result['type'];
                    break;

                case 'delete':
                    $result = deleteCustomer($_POST, $conn);
                    $message = $result['message'];
                    $message_type = $result['type'];
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// ===== GET DATA =====
// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE c.name LIKE '%$search%' OR c.phone LIKE '%$search%' 
                     OR c.email LIKE '%$search%' OR c.car_plate LIKE '%$search%'";
}

// Fetch all customers
$query = "SELECT c.*, u.username FROM customers c 
          LEFT JOIN users u ON c.user_id = u.id 
          $where_clause ORDER BY c.date_added DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    $message = "Error fetching customers: " . mysqli_error($conn);
    $message_type = "error";
}

// Get customer for editing
$edit_customer = null;
if (isset($_GET['edit_id'])) {
    $edit_stmt = mysqli_prepare($conn, 
        "SELECT c.*, u.username FROM customers c 
         LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?"
    );
    mysqli_stmt_bind_param($edit_stmt, "i", intval($_GET['edit_id']));
    mysqli_stmt_execute($edit_stmt);
    $edit_result = mysqli_stmt_get_result($edit_stmt);
    $edit_customer = mysqli_fetch_assoc($edit_result);
    mysqli_stmt_close($edit_stmt);
}

// Get available services
$services_result = mysqli_query($conn, "SELECT service_type FROM services ORDER BY service_type ASC");

// Get customer statistics
$stats = getCustomerStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Lewis Car Wash Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===== RESET AND BASE STYLES ===== */
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

        /* ===== HEADER STYLES ===== */
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

        /* ===== MAIN CONTAINER ===== */
        .customers-container {
            max-width: 1400px;
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

        /* ===== STATISTICS CARDS ===== */
        .customer-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #333;
            text-align: center;
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .stat-number {
            font-size: 2rem;
            color: #ffd700;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        /* ===== SEARCH SECTION ===== */
        .search-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid #333;
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #444;
            border-radius: 8px;
            background: #2d2d2d;
            color: #ffffff;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #ffd700;
        }

        .search-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        /* ===== ALERTS ===== */
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
        
        /* ===== FORM STYLES ===== */
        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2.5rem;
            border-radius: 16px;
            border: 2px solid #333;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
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
            color: #e0e0e0;
            font-weight: 500;
            font-size: 1rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #2d2d2d;
            color: #ffffff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* ===== BUTTON STYLES ===== */
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
        
        /* ===== TABLE STYLES ===== */
        .customers-table {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 16px;
            border: 2px solid #333;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.6s ease forwards;
            animation-delay: 0.2s;
            opacity: 0;
            transform: translateY(30px);
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
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            color: #ffd700;
            font-weight: 600;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
        }
        
        td {
            color: #e0e0e0;
            font-size: 0.9rem;
        }
        
        tr:hover td {
            background: rgba(255, 215, 0, 0.05);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .no-customers {
            text-align: center;
            padding: 3rem;
            color: #b0b0b0;
            font-style: italic;
            font-size: 1.1rem;
        }

        .no-customers i {
            font-size: 3rem;
            color: #444;
            margin-bottom: 1rem;
            display: block;
        }

        /* ===== ANIMATIONS ===== */
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .customers-container {
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

            .form-container {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .customer-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
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

    <div class="customers-container">
        <!-- ===== PAGE HEADER ===== -->
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Manage Customers</h1>
            <p>View, edit, and manage customer accounts</p>
        </div>

        <!-- ===== CUSTOMER STATISTICS ===== -->
        <div class="customer-stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['today'] ?></div>
                <div class="stat-label">Added Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['week'] ?></div>
                <div class="stat-label">Added This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['month'] ?></div>
                <div class="stat-label">Added This Month</div>
            </div>
        </div>

        <!-- ===== SEARCH SECTION ===== -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search customers by name, phone, email, or car plate..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="view_customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ===== SUCCESS/ERROR MESSAGES ===== -->
        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ===== EDIT CUSTOMER FORM (Only shown when editing) ===== -->
        <?php if ($edit_customer): ?>
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-edit"></i>
                Edit Customer
            </h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="customer_id" value="<?= $edit_customer['id'] ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required 
                               maxlength="100"
                               value="<?= htmlspecialchars($edit_customer['name']) ?>"
                               placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               required 
                               maxlength="20"
                               value="<?= htmlspecialchars($edit_customer['phone']) ?>"
                               placeholder="Enter phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required 
                               maxlength="100"
                               value="<?= htmlspecialchars($edit_customer['email']) ?>"
                               placeholder="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="car_plate">Car Plate Number</label>
                        <input type="text" 
                               id="car_plate" 
                               name="car_plate" 
                               maxlength="20"
                               value="<?= htmlspecialchars($edit_customer['car_plate']) ?>"
                               placeholder="Enter car plate number">
                    </div>
                    
                    <div class="form-group">
                        <label for="car_type">Car Type</label>
                        <input type="text" 
                               id="car_type" 
                               name="car_type" 
                               maxlength="50"
                               value="<?= htmlspecialchars($edit_customer['car_type']) ?>"
                               placeholder="Enter car type/model">
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Preferred Service</label>
                        <select id="service_type" name="service_type">
                            <option value="">Select a service</option>
                            <?php 
                            mysqli_data_seek($services_result, 0);
                            while ($service = mysqli_fetch_assoc($services_result)): 
                            ?>
                                <option value="<?= htmlspecialchars($service['service_type']) ?>"
                                        <?= ($edit_customer['service_type'] === $service['service_type']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($service['service_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_date">Next Service Date</label>
                        <input type="datetime-local" 
                               id="service_date" 
                               name="service_date"
                               value="<?= $edit_customer['service_date'] ? date('Y-m-d\TH:i', strtotime($edit_customer['service_date'])) : '' ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Customer
                    </button>
                    <a href="view_customers.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ===== CUSTOMERS TABLE ===== -->
        <div class="customers-table">
            <h2 class="table-title">
                <i class="fas fa-list"></i> 
                <?= !empty($search) ? "Search Results for \"$search\"" : "All Customers" ?>
            </h2>
            
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Car Details</th>
                                <th>Service</th>
                                <th>Next Service</th>
                                <th>Username</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= isset($row['email']) ? htmlspecialchars($row['email']) : '<em>N/A</em>' ?></td>

                                    <td>
                                        <?php if ($row['car_plate'] || $row['car_type']): ?>
                                            <strong><?= htmlspecialchars($row['car_plate']) ?></strong><br>
                                            <small><?= htmlspecialchars($row['car_type']) ?></small>
                                        <?php else: ?>
                                            <em>No details</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['service_type']) ?: '<em>N/A</em>' ?></td>
                                    <td>
                                        <?= $row['service_date'] ? date('M d, Y H:i', strtotime($row['service_date'])) : '<em>N/A</em>' ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['username']) ?: '<em>Guest</em>' ?></td>
                                    <td><?= date('M d, Y', strtotime($row['date_added'])) ?></td>
                                    <td class="actions">
                                        <a href="view_customers.php?edit_id=<?= $row['id'] ?>" class="btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="customer_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-delete">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-customers">
                    <i class="fas fa-user-slash"></i>
                    No customers found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
