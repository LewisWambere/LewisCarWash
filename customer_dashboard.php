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
        error_log("Bookings query failed: " . mysqli_error($conn));
    }
    
    // Get recent bookings
    $recent_bookings_query = "SELECT * FROM bookings WHERE customer_username='$username' ORDER BY booking_date DESC LIMIT 3";
    $recent_bookings = mysqli_query($conn, $recent_bookings_query);
    if (!$recent_bookings) {
        error_log("Recent bookings query failed: " . mysqli_error($conn));
        $recent_bookings = false;
    }
} else {
    $customer_stats['bookings'] = 0;
    $recent_bookings = false;
}

// Get available services - FIXED: Removed status filter since the column doesn't exist
$services_query = "SHOW TABLES LIKE 'services'";
$services_exists = mysqli_query($conn, $services_query);
if ($services_exists && mysqli_num_rows($services_exists) > 0) {
    $available_services_query = "SELECT * FROM services LIMIT 6";
    $available_services = mysqli_query($conn, $available_services_query);
    if (!$available_services) {
        error_log("Available services query failed: " . mysqli_error($conn));
        $available_services = false;
    }
    
    $services_count_query = "SELECT COUNT(*) as total FROM services";
    $services_result = mysqli_query($conn, $services_count_query);
    if ($services_result) {
        $services_data = mysqli_fetch_assoc($services_result);
        $customer_stats['services'] = $services_data['total'] ?? 0;
    } else {
        $customer_stats['services'] = 0;
        error_log("Services count query failed: " . mysqli_error($conn));
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
        error_log("Transactions query failed: " . mysqli_error($conn));
    }
    
    // Get total spent
    $total_spent_query = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE customer_username='$username'";
    $spent_result = mysqli_query($conn, $total_spent_query);
    if ($spent_result) {
        $spent_data = mysqli_fetch_assoc($spent_result);
        $customer_stats['total_spent'] = $spent_data['total'] ?? 0;
    } else {
        $customer_stats['total_spent'] = 0;
        error_log("Total spent query failed: " . mysqli_error($conn));
    }
} else {
    $customer_stats['transactions'] = 0;
    $customer_stats['total_spent'] = 0;
}

// Debug information (remove in production)
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Customer Stats: ";
    print_r($customer_stats);
    echo "\nDatabase connection status: " . (mysqli_ping($conn) ? "Connected" : "Disconnected");
    echo "</pre>";
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

        .book-btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
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

        /* Error message styling */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #f5c6cb;
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
    </style>
</head>
<body>
    <!-- Header -->
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
            <div class="user-section">
                <div class="welcome-text">
                    Welcome, <span class="username"><?= htmlspecialchars($username) ?></span>
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
                <a href="book_service.php" class="action-card">
                    <div class="icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Book New Service</h3>
                    <p>Schedule your next car wash</p>
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

        <!-- Available Services - FIXED: Updated to use correct column names -->
        <?php if ($available_services && mysqli_num_rows($available_services) > 0): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-car-side"></i> Available Services
            </h2>
            <div class="services-grid">
                <?php while ($service = mysqli_fetch_assoc($available_services)): ?>
                <div class="service-card">
                    <h3><?= htmlspecialchars($service['service_type']) ?></h3>
                    <div class="price">KES <?= number_format($service['price'], 2) ?></div>
                    <p>Professional <?= strtolower(htmlspecialchars($service['service_type'])) ?> service for your vehicle</p>
                    <a href="book_service.php?service_id=<?= $service['id'] ?>" class="book-btn">
                        <i class="fas fa-calendar-plus"></i> Book Now
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-car-side"></i> Available Services
            </h2>
            <div class="activity-card">
                <p style="text-align: center; color: #666; padding: 2rem;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: #ffd700; margin-bottom: 1rem;"></i><br>
                    Services will be displayed here once they are added to the system.
                </p>
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
                        <h4><?= htmlspecialchars($booking['service_name']) ?></h4>
                        <p>Booked on <?= date('M j, Y', strtotime($booking['booking_date'])) ?></p>
                    </div>
                    <span class="activity-status status-<?= $booking['status'] ?>">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Add interactive effects
        document.querySelectorAll('.action-card, .service-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-5px) scale(1)';
            });
        });

        // Add welcome animation
        window.addEventListener('load', function() {
            document.querySelector('.dashboard-title').style.animation = 'fadeInUp 0.8s ease forwards';
        });
    </script>
</body>
</html>