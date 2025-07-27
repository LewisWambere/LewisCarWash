<?php
session_start();
include('db_connection.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get some basic stats for the dashboard
$stats = [];

// Total customers
$customer_query = "SELECT COUNT(*) as total FROM users WHERE role='customer'";
$customer_result = mysqli_query($conn, $customer_query);
$stats['customers'] = mysqli_fetch_assoc($customer_result)['total'];

// Total attendants
$attendant_query = "SELECT COUNT(*) as total FROM users WHERE role='attendant'";
$attendant_result = mysqli_query($conn, $attendant_query);
$stats['attendants'] = mysqli_fetch_assoc($attendant_result)['total'];

// Total services (if services table exists)
$services_query = "SHOW TABLES LIKE 'services'";
$services_exists = mysqli_query($conn, $services_query);
if (mysqli_num_rows($services_exists) > 0) {
    $service_count_query = "SELECT COUNT(*) as total FROM services";
    $service_result = mysqli_query($conn, $service_count_query);
    $stats['services'] = mysqli_fetch_assoc($service_result)['total'];
} else {
    $stats['services'] = 0;
}

// Total transactions (if transactions table exists)
$transactions_query = "SHOW TABLES LIKE 'transactions'";
$transactions_exists = mysqli_query($conn, $transactions_query);
if (mysqli_num_rows($transactions_exists) > 0) {
    $transaction_count_query = "SELECT COUNT(*) as total FROM transactions";
    $transaction_result = mysqli_query($conn, $transaction_count_query);
    $stats['transactions'] = mysqli_fetch_assoc($transaction_result)['total'];
} else {
    $stats['transactions'] = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lewis Car Wash</title>
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

        /* Action Cards */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .action-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2.5rem;
            border-radius: 16px;
            border: 2px solid #333;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #ffd700;
            box-shadow: 0 15px 35px rgba(255, 215, 0, 0.1);
            color: white;
            text-decoration: none;
        }

        .action-card .icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 1.5rem;
        }

        .action-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .action-card p {
            color: #b0b0b0;
            line-height: 1.6;
        }

        .action-card .arrow {
            margin-top: 1rem;
            font-size: 1.2rem;
            color: #ffd700;
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

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card, .action-card {
                padding: 1.5rem;
            }
        }

        /* Animation */
        .stat-card, .action-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        .action-card:nth-child(1) { animation-delay: 0.5s; }
        .action-card:nth-child(2) { animation-delay: 0.6s; }
        .action-card:nth-child(3) { animation-delay: 0.7s; }

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
            <h2>Admin Dashboard</h2>
            <p>Manage your car wash business efficiently</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="number"><?= $stats['customers'] ?></div>
                <div class="label">Total Customers</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="number"><?= $stats['attendants'] ?></div>
                <div class="label">Attendants</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="number"><?= $stats['services'] ?></div>
                <div class="label">Available Services</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="number"><?= $stats['transactions'] ?></div>
                <div class="label">Total Transactions</div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="actions-grid">
            <a href="view_customers.php" class="action-card">
                <div class="icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Manage Customers</h3>
                <p>View, add, edit, and manage customer accounts and information</p>
                <div class="arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="services.php" class="action-card">
                <div class="icon">
                    <i class="fas fa-car-side"></i>
                </div>
                <h3>Manage Services</h3>
                <p>Add, edit, and manage car wash services, pricing, and packages</p>
                <div class="arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>

            <a href="transactions.php" class="action-card">
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3>View Transactions</h3>
                <p>Monitor all transactions, payments, and financial reports</p>
                <div class="arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <script>
        // Add some interactive effects
        document.querySelectorAll('.action-card').forEach(card => {
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