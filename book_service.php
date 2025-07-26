<?php
session_start();
include('db_connection.php');

// Check if user is logged in and is customer
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions via AJAX
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_to_cart') {
        $service_id = intval($_POST['service_id']);
        
        // Get service details
        $service_query = "SELECT * FROM services WHERE id = $service_id";
        $service_result = mysqli_query($conn, $service_query);
        
        if ($service_result && mysqli_num_rows($service_result) > 0) {
            $service = mysqli_fetch_assoc($service_result);
            
            // Check if service already in cart
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $service_id) {
                    $item['quantity'] += 1;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $service['id'],
                    'name' => $service['service_type'],
                    'price' => $service['price'],
                    'quantity' => 1
                ];
            }
            
            echo json_encode(['success' => true, 'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Service not found']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'remove_from_cart') {
        $service_id = intval($_POST['service_id']);
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $service_id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                break;
            }
        }
        
        echo json_encode(['success' => true, 'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
        exit();
    }
    
    if ($_POST['action'] === 'update_quantity') {
        $service_id = intval($_POST['service_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or less
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['id'] == $service_id) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                    break;
                }
            }
        } else {
            // Update quantity
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $service_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
        
        echo json_encode(['success' => true, 'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
        exit();
    }
    
    if ($_POST['action'] === 'get_cart') {
        $cart_total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_total += $item['price'] * $item['quantity'];
        }
        
        echo json_encode([
            'success' => true, 
            'cart' => $_SESSION['cart'], 
            'total' => $cart_total,
            'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))
        ]);
        exit();
    }
    
    if ($_POST['action'] === 'clear_cart') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        exit();
    }
}

// Get customer data and statistics
$customer_stats = [];

// Get customer's booking history if bookings table exists
$bookings_query = "SHOW TABLES LIKE 'bookings'";
$bookings_exists = mysqli_query($conn, $bookings_query);
if ($bookings_exists && mysqli_num_rows($bookings_exists) > 0) {
    $customer_bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE customer_username='$username'";
    $bookings_result = mysqli_query($conn, $customer_bookings_query);
    
    if ($bookings_result) {
        $booking_data = mysqli_fetch_assoc($bookings_result);
        $customer_stats['bookings'] = $booking_data['total'] ?? 0;
    } else {
        $customer_stats['bookings'] = 0;
    }
    
    // Get recent bookings
    $recent_bookings_query = "SELECT * FROM bookings WHERE customer_username='$username' ORDER BY booking_date DESC LIMIT 3";
    $recent_bookings = mysqli_query($conn, $recent_bookings_query);
    if (!$recent_bookings) {
        $recent_bookings = false;
    }
} else {
    $customer_stats['bookings'] = 0;
    $recent_bookings = false;
}

// Get available services
$services_query = "SHOW TABLES LIKE 'services'";
$services_exists = mysqli_query($conn, $services_query);
if ($services_exists && mysqli_num_rows($services_exists) > 0) {
    $available_services_query = "SELECT * FROM services ORDER BY service_type";
    $available_services = mysqli_query($conn, $available_services_query);
    if (!$available_services) {
        $available_services = false;
    }
    
    $services_count_query = "SELECT COUNT(*) as total FROM services";
    $services_result = mysqli_query($conn, $services_count_query);
    if ($services_result) {
        $services_data = mysqli_fetch_assoc($services_result);
        $customer_stats['services'] = $services_data['total'] ?? 0;
    } else {
        $customer_stats['services'] = 0;
    }
} else {
    $available_services = false;
    $customer_stats['services'] = 0;
}

// Get customer transactions if transactions table exists
$transactions_query = "SHOW TABLES LIKE 'transactions'";
$transactions_exists = mysqli_query($conn, $transactions_query);
if ($transactions_exists && mysqli_num_rows($transactions_exists) > 0) {
    $customer_transactions_query = "SELECT COUNT(*) as total FROM transactions WHERE customer_username='$username'";
    $transactions_result = mysqli_query($conn, $customer_transactions_query);
    if ($transactions_result) {
        $transactions_data = mysqli_fetch_assoc($transactions_result);
        $customer_stats['transactions'] = $transactions_data['total'] ?? 0;
    } else {
        $customer_stats['transactions'] = 0;
    }
    
    // Get total spent
    $total_spent_query = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE customer_username='$username'";
    $spent_result = mysqli_query($conn, $total_spent_query);
    if ($spent_result) {
        $spent_data = mysqli_fetch_assoc($spent_result);
        $customer_stats['total_spent'] = $spent_data['total'] ?? 0;
    } else {
        $customer_stats['total_spent'] = 0;
    }
} else {
    $customer_stats['transactions'] = 0;
    $customer_stats['total_spent'] = 0;
}

// Calculate cart total
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Lewis Car Wash</title>
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

        .cart-icon {
            position: relative;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 0.7rem;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 1rem;
        }

        .cart-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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

        /* Main Content */
        .main-content {
            max-width: 1200px;
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

        /* Stats Cards */
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
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border-color: #ffd700;
        }

        .stat-card .icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2rem;
            border-radius: 16px;
            border: 2px solid #333;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #ffd700;
            box-shadow: 0 15px 35px rgba(255, 215, 0, 0.1);
            color: white;
            text-decoration: none;
        }

        .action-card .icon {
            font-size: 2.5rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .action-card h3 {
            color: #ffd700;
            margin-bottom: 0.5rem;
        }

        .action-card p {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        /* Shopping Cart Section */
        .cart-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .cart-header h3 {
            color: #1a1a1a;
            font-size: 1.5rem;
        }

        .cart-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #ffd700;
        }

        .cart-items {
            max-height: 400px;
            overflow-y: auto;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .cart-item:last-child {
            margin-bottom: 0;
        }

        .cart-item-info h4 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .cart-item-price {
            color: #666;
            font-weight: 600;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            background: #ffd700;
            color: #1a1a1a;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-weight: bold;
        }

        .quantity-display {
            background: white;
            border: 1px solid #ddd;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            min-width: 40px;
            text-align: center;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-cart i {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .cart-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .checkout-btn, .clear-cart-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            flex: 1;
        }

        .checkout-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }

        .clear-cart-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .checkout-btn:hover, .clear-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Services Section */
        .section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2rem;
            color: #ffd700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .service-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .service-card:hover {
            transform: translateY(-5px);
            border-color: #ffd700;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .service-card h3 {
            color: #1a1a1a;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .service-card .price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .service-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .add-to-cart-btn.added {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        /* Recent Activity */
        .activity-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info h4 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .activity-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .activity-status {
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
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

            .dashboard-title h2 {
                font-size: 2rem;
            }

            .stats-grid, .services-grid, .quick-actions {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .cart-item {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .cart-item-controls {
                justify-content: center;
            }

            .cart-actions {
                flex-direction: column;
            }
        }

        /* Animation */
        .stat-card, .service-card, .action-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
            </div>
            <div class="user-section">
                <div class="welcome-text">
                    Welcome, <span class="username"><?= htmlspecialchars($username) ?></span>
                </div>
                <div class="cart-icon" onclick="toggleCart()">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount"><?= $cart_count ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Title -->
        <div class="dashboard-title">
            <h2>Customer Dashboard</h2>
            <p>Book services, track your history, and manage your profile</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="number"><?= $customer_stats['bookings'] ?></div>
                <div class="label">Total Bookings</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="number"><?= $customer_stats['services'] ?></div>
                <div class="label">Available Services</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="number"><?= $customer_stats['transactions'] ?></div>
                <div class="label">Completed Orders</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="number">KES <?= number_format($customer_stats['total_spent'], 2) ?></div>
                <div class="label">Total Spent</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div class="quick-actions">
                <a href="#" onclick="scrollToServices()" class="action-card">
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Add Services</h3>
                    <p>Browse and add services to cart</p>
                </a>

                <a href="my_bookings.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>My Bookings</h3>
                    <p>View booking history</p>
                </a>

                <a href="profile.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>My Profile</h3>
                    <p>Update your information</p>
                </a>

                <a href="contact.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Contact Support</h3>
                    <p>Get help when you need it</p>
                </a>
            </div>
        </div>

        <!-- Shopping Cart Section -->
        <div class="cart-section" id="cartSection" style="display: <?= !empty($_SESSION['cart']) ? 'block' : 'none' ?>;">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Your Cart</h3>
                <div class="cart-total">Total: KES <span id="cartTotal"><?= number_format($cart_total, 2) ?></span></div>
            </div>
            
            <div class="cart-items" id="cartItems">
                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="cart-item" data-service-id="<?= $item['id'] ?>">
                        <div class="cart-item-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <div class="cart-item-price">KES <?= number_format($item['price'], 2) ?> each</div>
                        </div>
                        <div class="cart-item-controls">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)">-</button>
                                <div class="quantity-display"><?= $item['quantity'] ?></div>
                                <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeFromCart(<?= $item['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Add some services to get started!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($_SESSION['cart'])): ?>
            <div class="cart-actions">
                <button class="checkout-btn" onclick="proceedToCheckout()" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </button>
                <button class="clear-cart-btn" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i> Clear Cart
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Available Services Section -->
        <?php if ($available_services): ?>
        <div class="section" id="servicesSection">
            <h2 class="section-title">
                <i class="fas fa-car"></i> Available Services
            </h2>
            <div class="services-grid">
                <?php while ($service = mysqli_fetch_assoc($available_services)): ?>
                <div class="service-card" data-service-id="<?= $service['id'] ?>">
                    <h3><?= htmlspecialchars($service['service_type']) ?></h3>
                    <div class="price">KES <?= number_format($service['price'], 2) ?></div>
                    <p><?= htmlspecialchars($service['description'] ?? 'Professional car wash service') ?></p>
                    <button class="add-to-cart-btn" onclick="addToCart(<?= $service['id'] ?>)">
                        <i class="fas fa-plus"></i> Add to Cart
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if ($recent_bookings && mysqli_num_rows($recent_bookings) > 0): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-clock"></i> Recent Activity
            </h2>
            <div class="activity-card">
                <?php while ($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <h4><?= htmlspecialchars($booking['service_type'] ?? 'Service Booking') ?></h4>
                        <p>Booked on <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                    </div>
                    <div class="activity-status <?= $booking['status'] === 'completed' ? 'status-completed' : 'status-pending' ?>">
                        <?= ucfirst($booking['status'] ?? 'pending') ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // Global variables
        let cartVisible = false;
        
        // Toggle cart visibility
        function toggleCart() {
            const cartSection = document.getElementById('cartSection');
            cartVisible = !cartVisible;
            
            if (cartVisible) {
                cartSection.style.display = 'block';
                cartSection.scrollIntoView({ behavior: 'smooth' });
            } else {
                cartSection.style.display = 'none';
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Add service to cart
        function addToCart(serviceId) {
            const button = document.querySelector(`[data-service-id="${serviceId}"] .add-to-cart-btn`);
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_to_cart&service_id=${serviceId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> Added!';
                    button.classList.add('added');
                    
                    // Update cart count
                    document.getElementById('cartCount').textContent = data.cart_count;
                    
                    // Show cart section and refresh it
                    document.getElementById('cartSection').style.display = 'block';
                    refreshCart();
                    
                    showNotification('Service added to cart!');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.classList.remove('added');
                        button.disabled = false;
                    }, 2000);
                } else {
                    showNotification(data.message || 'Failed to add service', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        // Remove item from cart
        function removeFromCart(serviceId) {
            if (!confirm('Are you sure you want to remove this service from your cart?')) {
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_cart&service_id=${serviceId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    document.getElementById('cartCount').textContent = data.cart_count;
                    
                    // Refresh cart display
                    refreshCart();
                    
                    showNotification('Service removed from cart');
                } else {
                    showNotification('Failed to remove service', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        // Update quantity
        function updateQuantity(serviceId, newQuantity) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&service_id=${serviceId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    document.getElementById('cartCount').textContent = data.cart_count;
                    
                    // Refresh cart display
                    refreshCart();
                } else {
                    showNotification('Failed to update quantity', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        // Clear entire cart
        function clearCart() {
            if (!confirm('Are you sure you want to clear your entire cart?')) {
                return;
            }

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    document.getElementById('cartCount').textContent = '0';
                    
                    // Hide cart section
                    document.getElementById('cartSection').style.display = 'none';
                    
                    showNotification('Cart cleared successfully');
                } else {
                    showNotification('Failed to clear cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        // Refresh cart display
        function refreshCart() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartItems = document.getElementById('cartItems');
                    const cartTotal = document.getElementById('cartTotal');
                    
                    // Update total
                    cartTotal.textContent = new Intl.NumberFormat('en-KE', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(data.total);
                    
                    // Update cart items
                    if (data.cart.length === 0) {
                        cartItems.innerHTML = `
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>Your cart is empty</h3>
                                <p>Add some services to get started!</p>
                            </div>
                        `;
                        document.getElementById('cartSection').style.display = 'none';
                    } else {
                        let cartHTML = '';
                        data.cart.forEach(item => {
                            cartHTML += `
                                <div class="cart-item" data-service-id="${item.id}">
                                    <div class="cart-item-info">
                                        <h4>${item.name}</h4>
                                        <div class="cart-item-price">KES ${new Intl.NumberFormat('en-KE', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }).format(item.price)} each</div>
                                    </div>
                                    <div class="cart-item-controls">
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                            <div class="quantity-display">${item.quantity}</div>
                                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                                        </div>
                                        <button class="remove-btn" onclick="removeFromCart(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        
                        cartHTML += `
                            <div class="cart-actions">
                                <button class="checkout-btn" onclick="proceedToCheckout()">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </button>
                                <button class="clear-cart-btn" onclick="clearCart()">
                                    <i class="fas fa-trash-alt"></i> Clear Cart
                                </button>
                            </div>
                        `;
                        
                        cartItems.innerHTML = cartHTML;
                        document.getElementById('cartSection').style.display = 'block';
                    }
                    
                    // Update cart count
                    document.getElementById('cartCount').textContent = data.cart_count;
                }
            })
            .catch(error => {
                console.error('Error refreshing cart:', error);
            });
        }

        // Proceed to checkout
        function proceedToCheckout() {
            // You can redirect to a separate checkout page or handle checkout here
            if (confirm('Proceed to checkout? This will redirect you to the booking confirmation page.')) {
                window.location.href = 'checkout.php';
            }
        }

        // Scroll to services section
        function scrollToServices() {
            const servicesSection = document.getElementById('servicesSection');
            if (servicesSection) {
                servicesSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Initialize cart display on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cartCount = parseInt(document.getElementById('cartCount').textContent);
            if (cartCount > 0) {
                document.getElementById('cartSection').style.display = 'block';
            }
        });
    </script>
</body>
</html>