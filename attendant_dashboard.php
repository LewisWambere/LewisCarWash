<?php
session_start();
include('db_connection.php');

// Check if user is logged in and is attendant
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'attendant') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Function to safely execute queries and handle errors
function safe_query($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result === false) {
        error_log("Database query failed: " . mysqli_error($conn) . " | Query: " . $query);
        return false;
    }
    return $result;
}

// Function to safely fetch associative array
function safe_fetch_assoc($result) {
    if ($result === false) {
        return null;
    }
    return mysqli_fetch_assoc($result);
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'start_service':
            $booking_id = intval($_POST['booking_id']);
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = 'in_progress', started_at = NOW() WHERE id = ? AND status = 'waiting'");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $booking_id);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            exit();
            
        case 'complete_service':
            $booking_id = intval($_POST['booking_id']);
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET status = 'completed', completed_at = NOW() WHERE id = ? AND status = 'in_progress'");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $booking_id);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            exit();
            
        case 'get_dashboard_data':
            // Get current statistics
            $stats = [];
            
            // Customers waiting
            $waiting_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'waiting'";
            $waiting_result = safe_query($conn, $waiting_query);
            $waiting_data = safe_fetch_assoc($waiting_result);
            $stats['waiting'] = $waiting_data ? intval($waiting_data['count']) : 0;
            
            // Services in progress
            $progress_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'in_progress'";
            $progress_result = safe_query($conn, $progress_query);
            $progress_data = safe_fetch_assoc($progress_result);
            $stats['in_progress'] = $progress_data ? intval($progress_data['count']) : 0;
            
            // Completed today
            $completed_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'completed' AND DATE(completed_at) = CURDATE()";
            $completed_result = safe_query($conn, $completed_query);
            $completed_data = safe_fetch_assoc($completed_result);
            $stats['completed'] = $completed_data ? intval($completed_data['count']) : 0;
            
            // Revenue today - Check if services table exists
            $revenue_query = "SELECT COALESCE(SUM(s.price), 0) as revenue FROM bookings b 
                             LEFT JOIN services s ON b.service_type = s.service_type 
                             WHERE b.status = 'completed' AND DATE(b.completed_at) = CURDATE()";
            $revenue_result = safe_query($conn, $revenue_query);
            $revenue_data = safe_fetch_assoc($revenue_result);
            $stats['revenue'] = $revenue_data ? floatval($revenue_data['revenue']) : 0;
            
            // Get waiting queue
            $waiting_queue_query = "SELECT b.*, s.price, s.service_type as service_name 
                                   FROM bookings b 
                                   LEFT JOIN services s ON b.service_type = s.service_type 
                                   WHERE b.status = 'waiting' 
                                   ORDER BY b.booking_date ASC";
            $waiting_queue_result = safe_query($conn, $waiting_queue_query);
            $waiting_queue = [];
            if ($waiting_queue_result) {
                while ($row = mysqli_fetch_assoc($waiting_queue_result)) {
                    $waiting_queue[] = $row;
                }
            }
            
            // Get services in progress
            $progress_queue_query = "SELECT b.*, s.price, s.service_type as service_name 
                                    FROM bookings b 
                                    LEFT JOIN services s ON b.service_type = s.service_type 
                                    WHERE b.status = 'in_progress' 
                                    ORDER BY b.started_at ASC";
            $progress_queue_result = safe_query($conn, $progress_queue_query);
            $progress_queue = [];
            if ($progress_queue_result) {
                while ($row = mysqli_fetch_assoc($progress_queue_result)) {
                    $progress_queue[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'waiting_queue' => $waiting_queue,
                'progress_queue' => $progress_queue
            ]);
            exit();
            
        case 'add_walk_in':
            $customer_name = trim($_POST['customer_name']);
            $customer_phone = trim($_POST['customer_phone']);
            $service_type = trim($_POST['service_type']);
            
            if (empty($customer_name) || empty($service_type)) {
                echo json_encode(['success' => false, 'error' => 'Name and service type are required']);
                exit();
            }
            
            // Insert booking
            $stmt = mysqli_prepare($conn, "INSERT INTO bookings (customer_username, customer_name, customer_phone, service_type, status, booking_date) VALUES (?, ?, ?, ?, 'waiting', NOW())");
            if ($stmt) {
                $customer_username = 'walk_in_' . time();
                mysqli_stmt_bind_param($stmt, "ssss", $customer_username, $customer_name, $customer_phone, $service_type);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            exit();
            
        case 'ping':
            echo json_encode(['success' => true, 'timestamp' => time()]);
            exit();
    }
}

// Get initial data for page load
$stats = [];

// Customers waiting
$waiting_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'waiting'";
$waiting_result = safe_query($conn, $waiting_query);
$waiting_data = safe_fetch_assoc($waiting_result);
$stats['waiting'] = $waiting_data ? intval($waiting_data['count']) : 0;

// Services in progress
$progress_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'in_progress'";
$progress_result = safe_query($conn, $progress_query);
$progress_data = safe_fetch_assoc($progress_result);
$stats['in_progress'] = $progress_data ? intval($progress_data['count']) : 0;

// Completed today
$completed_query = "SELECT COUNT(*) as count FROM bookings WHERE status = 'completed' AND DATE(completed_at) = CURDATE()";
$completed_result = safe_query($conn, $completed_query);
$completed_data = safe_fetch_assoc($completed_result);
$stats['completed'] = $completed_data ? intval($completed_data['count']) : 0;

// Revenue today
$revenue_query = "SELECT COALESCE(SUM(s.price), 0) as revenue FROM bookings b 
                 LEFT JOIN services s ON b.service_type = s.service_type 
                 WHERE b.status = 'completed' AND DATE(b.completed_at) = CURDATE()";
$revenue_result = safe_query($conn, $revenue_query);
$revenue_data = safe_fetch_assoc($revenue_result);
$stats['revenue'] = $revenue_data ? floatval($revenue_data['revenue']) : 0;

// Get available services for walk-in form
$services_query = "SELECT * FROM services ORDER BY service_type";
$services_result = safe_query($conn, $services_query);
$services = [];
if ($services_result) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $services[] = $row;
    }
}

// If no services found, create default ones
if (empty($services)) {
    $default_services = [
        ['service_type' => 'Basic Wash', 'price' => 500],
        ['service_type' => 'Premium Wash', 'price' => 800],
        ['service_type' => 'Full Detail', 'price' => 1500],
        ['service_type' => 'Interior Only', 'price' => 600],
        ['service_type' => 'Exterior Only', 'price' => 400]
    ];
    
    // Try to create services table if it doesn't exist
    $create_services_table = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_type VARCHAR(100) NOT NULL UNIQUE,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (safe_query($conn, $create_services_table)) {
        // Insert default services
        foreach ($default_services as $service) {
            $insert_service = "INSERT IGNORE INTO services (service_type, price) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_service);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sd", $service['service_type'], $service['price']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Reload services
        $services_result = safe_query($conn, $services_query);
        if ($services_result) {
            while ($row = mysqli_fetch_assoc($services_result)) {
                $services[] = $row;
            }
        }
    }
    
    // If still no services, use defaults for display
    if (empty($services)) {
        $services = $default_services;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Dashboard - Lewis Car Wash</title>
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

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .welcome-text {
            font-size: 1.1rem;
            color: #e0e0e0;
        }

        .welcome-text .username {
            color: #ffd700;
            font-weight: 600;
        }

        .badge {
            background: #ffd700;
            color: #1a1a1a;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
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

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }

        .main-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-title h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dashboard-title p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .waiting .icon { color: #ff6b6b; }
        .progress .icon { color: #4ecdc4; }
        .completed .icon { color: #45b7d1; }
        .revenue .icon { color: #f9ca24; }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        .queue-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .queue-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 16px;
            border: 2px solid #333;
            overflow: hidden;
        }

        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #333;
        }

        .queue-title {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .queue-count {
            background: #ffd700;
            color: #1a1a1a;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .queue-items {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
        }

        .queue-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .queue-item:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #ffd700;
        }

        .queue-item:last-child {
            margin-bottom: 0;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .customer-info h4 {
            color: #ffd700;
            margin-bottom: 0.3rem;
        }

        .customer-info p {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .service-info {
            text-align: right;
        }

        .service-type {
            color: #4ecdc4;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .service-price {
            color: #f39c12;
            font-weight: bold;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-start {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
            flex: 1;
        }

        .btn-complete {
            background: linear-gradient(135deg, #45b7d1 0%, #3867d6 100%);
            color: white;
            flex: 1;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .empty-queue {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-queue i {
            font-size: 3rem;
            color: #444;
            margin-bottom: 1rem;
        }

        .quick-actions {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2rem;
            border-radius: 16px;
            border: 2px solid #333;
            margin-bottom: 3rem;
        }

        .quick-actions-title {
            color: #ffd700;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            border: 2px solid #ffd700;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #333;
        }

        .modal-title {
            color: #ffd700;
            font-size: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ffd700;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e0e0e0;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #444;
            border-radius: 8px;
            background: #2d2d2d;
            color: #ffffff;
            font-size: 1rem;
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

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-secondary {
            background: #666;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .error-message {
            background: #ff4757;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .queue-section {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .item-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .service-info {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
            </div>
            <div class="user-section">
                <div class="welcome-text">
                    Welcome, <span class="username"><?= htmlspecialchars($username) ?></span>
                    <span class="badge">ATTENDANT</span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-title">
            <h2>Attendant Dashboard</h2>
            <p>Manage customer queue and service operations</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card waiting">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="number" id="waitingCount"><?= $stats['waiting'] ?></div>
                <div class="label">Customers Waiting</div>
            </div>
            <div class="stat-card progress">
                <div class="icon"><i class="fas fa-cog fa-spin"></i></div>
                <div class="number" id="progressCount"><?= $stats['in_progress'] ?></div>
                <div class="label">Services In Progress</div>
            </div>
            <div class="stat-card completed">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number" id="completedCount"><?= $stats['completed'] ?></div>
                <div class="label">Completed Today</div>
            </div>
            <div class="stat-card revenue">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="number" id="revenueAmount">KES <?= number_format($stats['revenue'], 0) ?></div>
                <div class="label">Revenue Today</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3 class="quick-actions-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h3>
            <div class="action-buttons">
                <button class="action-btn" onclick="openWalkInModal()">
                    <i class="fas fa-user-plus"></i> Add Walk-in Customer
                </button>
                <button class="action-btn" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh Dashboard
                </button>
                <button class="action-btn" onclick="window.location.href='customers.php'">
                    <i class="fas fa-users"></i> View All Customers
                </button>
            </div>
        </div>

        <!-- Service Queue -->
        <div class="queue-section">
            <!-- Waiting Queue -->
            <div class="queue-card">
                <div class="queue-header">
                    <div class="queue-title">
                        <i class="fas fa-hourglass-half"></i> Waiting for Service
                    </div>
                    <div class="queue-count" id="waitingQueueCount">0</div>
                </div>
                <div class="queue-items" id="waitingQueue">
                    <div class="empty-queue">
                        <i class="fas fa-smile"></i>
                        <h3>No customers waiting!</h3>
                        <p>All caught up</p>
                    </div>
                </div>
            </div>

            <!-- In Progress Queue -->
            <div class="queue-card">
                <div class="queue-header">
                    <div class="queue-title">
                        <i class="fas fa-cog"></i> In Progress
                    </div>
                    <div class="queue-count" id="progressQueueCount">0</div>
                </div>
                <div class="queue-items" id="progressQueue">
                    <div class="empty-queue">
                        <i class="fas fa-coffee"></i>
                        <h3>No services in progress</h3>
                        <p>Ready to start new services</p>
                    </div>
                </div>
            </div>