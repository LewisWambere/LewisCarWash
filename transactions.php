<?php
require_once 'db_connection.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Build filter conditions
$conditions = [];

if (!empty($_GET['start_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $conditions[] = "t.payment_date >= '$start_date 00:00:00'";
}

if (!empty($_GET['end_date'])) {
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
    $conditions[] = "t.payment_date <= '$end_date 23:59:59'";
}

if (!empty($_GET['customer_search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['customer_search']);
    $conditions[] = "c.name LIKE '%$search%'";
}

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// ✅ FIXED: Replaced s.name with s.service_type
$query = "SELECT t.id, c.name AS customer_name, s.service_type AS service_name, t.amount_paid, t.payment_date 
          FROM transactions t 
          LEFT JOIN customers c ON t.customer_id = c.id 
          LEFT JOIN services s ON t.service_id = s.id 
          $whereClause
          ORDER BY t.payment_date DESC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Transaction query failed: " . mysqli_error($conn));
}

// Calculate total revenue
$revenue_query = "SELECT SUM(amount_paid) as total_revenue FROM transactions";
$revenue_result = mysqli_query($conn, $revenue_query);
if (!$revenue_result) {
    die("Revenue query failed: " . mysqli_error($conn));
}
$total_revenue = mysqli_fetch_assoc($revenue_result)['total_revenue'] ?? 0;

// Get transaction count
$count_query = "SELECT COUNT(*) as total_transactions FROM transactions";
$count_result = mysqli_query($conn, $count_query);
if (!$count_result) {
    die("Transaction count query failed: " . mysqli_error($conn));
}
$total_transactions = mysqli_fetch_assoc($count_result)['total_transactions'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Lewis Car Wash</title>
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
            line-height: 1.6;
        }

        /* Header Section */
        .header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 2rem 0;
            border-bottom: 3px solid #ffd700;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ffd700;
            letter-spacing: -1px;
        }

        .logo .tagline {
            font-size: 0.9rem;
            color: #cccccc;
            font-weight: 300;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #cccccc;
        }

        .user-info i {
            color: #ffd700;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
            color: white;
            padding: 0.75rem 1.5rem;
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
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.3);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .page-title .highlight {
            color: #ffd700;
        }

        .page-title p {
            font-size: 1.2rem;
            color: #cccccc;
            font-weight: 300;
        }

        /* Summary Cards */
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .summary-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid #444444;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .summary-card .icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .summary-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .summary-card .label {
            font-size: 1.1rem;
            color: #cccccc;
            font-weight: 500;
        }

        /* Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid #444444;
            margin-bottom: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .filter-section h3 {
            color: #ffd700;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #cccccc;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 1rem;
            border: 2px solid #444444;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #1a1a1a;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control::placeholder {
            color: #888888;
        }

        .btn-filter {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        /* Table Styles */
        .table-container {
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border-radius: 16px;
            border: 1px solid #444444;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table thead {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }

        .transactions-table th {
            padding: 1.5rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #ffd700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .transactions-table td {
            padding: 1.25rem 1rem;
            border-top: 1px solid #444444;
            color: #ffffff;
            font-size: 0.95rem;
        }

        .transactions-table tbody tr {
            transition: all 0.3s ease;
        }

        .transactions-table tbody tr:hover {
            background-color: rgba(255, 215, 0, 0.1);
            transform: scale(1.01);
        }

        .transaction-id {
            font-weight: 600;
            color: #ffd700;
        }

        .amount {
            font-weight: 700;
            color: #4ade80;
        }

        /* No Transactions State */
        .no-transactions {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #2d2d2d 0%, #3d3d3d 100%);
            border-radius: 16px;
            border: 1px solid #444444;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .no-transactions i {
            font-size: 4rem;
            color: #666666;
            margin-bottom: 1rem;
        }

        .no-transactions h3 {
            font-size: 1.5rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .no-transactions p {
            color: #cccccc;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .container {
                padding: 1rem;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            .transactions-table {
                min-width: 600px;
            }

            .transactions-table th,
            .transactions-table td {
                padding: 1rem 0.5rem;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.8rem;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .summary-card .value {
                font-size: 2rem;
            }

            .filter-section {
                padding: 1.5rem;
            }
        }

        /* Loading Animation */
        .btn-filter:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-filter.loading {
            position: relative;
            color: transparent;
        }

        .btn-filter.loading::after {
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
        .btn-filter:focus,
        .logout-btn:focus {
            outline: 2px solid #ffd700;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div>
                    <h1><i class="fas fa-car-wash"></i> Lewis</h1>
                    <p class="tagline">Premium Car Care Services</p>
                </div>
            </div>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1><span class="highlight">Transaction</span> Records</h1>
            <p>Monitor and analyze your business performance</p>
        </div>

        <!-- Summary Cards -->
        <div class="summary">
            <div class="summary-card">
                <i class="fas fa-coins icon"></i>
                <div class="value">KSh <?= number_format($total_revenue, 2) ?></div>
                <div class="label">Total Revenue</div>
            </div>
            <div class="summary-card">
                <i class="fas fa-receipt icon"></i>
                <div class="value"><?= number_format($total_transactions) ?></div>
                <div class="label">Total Transactions</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Transactions</h3>
            <form method="get" class="filters">
                <div class="filter-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $_GET['start_date'] ?? '' ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $_GET['end_date'] ?? '' ?>">
                </div>
                <div class="filter-group">
                    <label for="customer_search">Customer Name</label>
                    <input type="text" id="customer_search" name="customer_search" class="form-control" placeholder="Search by customer name" value="<?= $_GET['customer_search'] ?? '' ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Transaction ID</th>
                            <th><i class="fas fa-user"></i> Customer</th>
                            <th><i class="fas fa-car"></i> Service</th>
                            <th><i class="fas fa-money-bill-wave"></i> Amount Paid</th>
                            <th><i class="fas fa-calendar-alt"></i> Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="transaction-id">#<?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></td>
                                <td class="amount">KSh <?= number_format($row['amount_paid'], 2) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($row['payment_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-transactions">
                <i class="fas fa-receipt"></i>
                <h3>No Transactions Found</h3>
                <p>No transaction records match your current filters. Try adjusting your search criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add loading state to filter button on form submit
        document.querySelector('form').addEventListener('submit', function() {
            const button = document.querySelector('.btn-filter');
            button.classList.add('loading');
            button.disabled = true;
        });

        // Add smooth focus transitions for form controls
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Auto-resize table on window resize
        window.addEventListener('resize', function() {
            const tableContainer = document.querySelector('.table-container');
            if (tableContainer) {
                // Force redraw to handle responsive table behavior
                tableContainer.style.display = 'none';
                tableContainer.offsetHeight; // Trigger reflow
                tableContainer.style.display = '';
            }
        });
    </script>
</body>
</html>