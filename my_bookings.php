<?php
session_start();
include('db_connection.php');

// Check if user is logged in and is customer
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Get customer ID first
$customer_query = "SELECT id FROM customers WHERE username = '$username' OR name = '$username'";
$customer_result = mysqli_query($conn, $customer_query);

if (!$customer_result || mysqli_num_rows($customer_result) == 0) {
    // If customer doesn't exist in customers table, we'll use username for bookings
    $customer_id = null;
} else {
    $customer_data = mysqli_fetch_assoc($customer_result);
    $customer_id = $customer_data['id'];
}

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Check if booking belongs to current user and is cancellable
    $check_query = "SELECT * FROM bookings WHERE id = $booking_id AND customer_username = '$username' AND status IN ('pending', 'confirmed')";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $cancel_query = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = $booking_id";
        if (mysqli_query($conn, $cancel_query)) {
            $success_message = "Booking cancelled successfully!";
        } else {
            $error_message = "Failed to cancel booking. Please try again.";
        }
    } else {
        $error_message = "Invalid booking or booking cannot be cancelled.";
    }
}

// Build filter conditions for bookings
$conditions = ["customer_username = '$username'"];

if (!empty($_GET['status_filter'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status_filter']);
    $conditions[] = "status = '$status'";
}

if (!empty($_GET['start_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $conditions[] = "booking_date >= '$start_date'";
}

if (!empty($_GET['end_date'])) {
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
    $conditions[] = "booking_date <= '$end_date'";
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// Get bookings with service details
$bookings_query = "SELECT b.*, s.service_type, s.price as service_price 
                   FROM bookings b 
                   LEFT JOIN services s ON b.service_id = s.id 
                   $whereClause 
                   ORDER BY b.booking_date DESC, b.created_at DESC";

$bookings_result = mysqli_query($conn, $bookings_query);

// Get booking statistics
$stats_query = "SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent
                FROM bookings 
                WHERE customer_username = '$username'";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Lewis Car Wash</title>
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
            line-height: 1.6;
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
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #ffd700;
        }

        .stat-card .icon {
            font-size: 2rem;
            color: #ffd700;
            margin-bottom: 0.5rem;
        }

        .stat-card .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 0.3rem;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        /* Filter Section */
        .filter-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }

        .filter-section h3 {
            color: #1a1a1a;
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
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #ffffff;
            color: #333;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            border: none;
            padding: 0.8rem 1.5rem;
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
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        /* Bookings Grid */
        .bookings-grid {
            display: grid;
            gap: 2rem;
        }

        .booking-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #ffd700;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .booking-info h3 {
            color: #1a1a1a;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .booking-id {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .booking-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #cce5ff;
            color: #0066cc;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-item i {
            color: #ffd700;
            width: 16px;
        }

        .detail-item .label {
            font-weight: 500;
            color: #666;
        }

        .detail-item .value {
            color: #1a1a1a;
            font-weight: 600;
        }

        .booking-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-cancel:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-reschedule {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-reschedule:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }

        /* Empty State */
        .empty-bookings {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .empty-bookings i {
            font-size: 4rem;
            color: #ffd700;
            margin-bottom: 1rem;
        }

        .empty-bookings h3 {
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .empty-bookings p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .btn-book-now {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
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

        .notification.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .notification.error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .main-content {
                padding: 0 1rem;
            }

            .page-title h2 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .booking-header {
                flex-direction: column;
                align-items: stretch;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                justify-content: center;
            }
        }

        /* Animation */
        .booking-card {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .booking-card:nth-child(1) { animation-delay: 0.1s; }
        .booking-card:nth-child(2) { animation-delay: 0.2s; }
        .booking-card:nth-child(3) { animation-delay: 0.3s; }

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
                <h1><i class="fas fa-car-wash"></i> Lewis Car Wash</h1>
            </div>
            <a href="book_service.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Title -->
        <div class="page-title">
            <h2>My Bookings</h2>
            <p>Track and manage your car wash appointments</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
                <div class="number"><?= $stats['total_bookings'] ?></div>
                <div class="label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <div class="number"><?= $stats['completed_bookings'] ?></div>
                <div class="label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <div class="number"><?= $stats['pending_bookings'] + $stats['confirmed_bookings'] ?></div>
                <div class="label">Active</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <div class="number"><?= $stats['cancelled_bookings'] ?></div>
                <div class="label">Cancelled</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="number">KES <?= number_format($stats['total_spent'], 2) ?></div>
                <div class="label">Total Spent</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Bookings</h3>
            <form method="get" class="filters">
                <div class="filter-group">
                    <label for="status_filter">Status</label>
                    <select id="status_filter" name="status_filter" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= ($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= ($_GET['status_filter'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="completed" <?= ($_GET['status_filter'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($_GET['status_filter'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="start_date">From Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $_GET['start_date'] ?? '' ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $_GET['end_date'] ?? '' ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Bookings Grid -->
        <?php if ($bookings_result && mysqli_num_rows($bookings_result) > 0): ?>
            <div class="bookings-grid">
                <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-info">
                                <h3><?= htmlspecialchars($booking['service_type'] ?? 'Car Wash Service') ?></h3>
                                <div class="booking-id">Booking ID: #<?= $booking['id'] ?></div>
                            </div>
                            <div class="booking-status status-<?= $booking['status'] ?>">
                                <?= ucfirst($booking['status']) ?>
                            </div>
                        </div>

                        <div class="booking-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="label">Date:</span>
                                <span class="value"><?= date('M j, Y', strtotime($booking['booking_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span class="label">Time:</span>
                                <span class="value"><?= date('g:i A', strtotime($booking['booking_time'] ?? $booking['booking_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span class="label">Amount:</span>
                                <span class="value">KES <?= number_format($booking['total_amount'] ?? $booking['service_price'] ?? 0, 2) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span class="label">Location:</span>
                                <span class="value"><?= htmlspecialchars($booking['location'] ?? 'Main Branch') ?></span>
                            </div>
                        </div>

                        <?php if (!empty($booking['notes'])): ?>
                            <div class="booking-notes">
                                <strong>Notes:</strong> <?= htmlspecialchars($booking['notes']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                            <div class="booking-actions">
                                <button type="button" class="btn-reschedule" onclick="rescheduleBooking(<?= $booking['id'] ?>)">
                                    <i class="fas fa-calendar-edit"></i> Reschedule
                                </button>
                                <form method="post" style="display: inline-block;" onsubmit="return confirmCancel()">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn-cancel">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-bookings">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You haven't made any bookings yet or no bookings match your current filters.</p>
                <a href="book_service.php" class="btn-book-now">
                    <i class="fas fa-plus"></i> Book Your First Service
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // Show notification function
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 5000);
        }

        // Confirm cancellation
        function confirmCancel() {
            return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');
        }

        // Reschedule booking (placeholder function)
        function rescheduleBooking(bookingId) {
            alert('Reschedule functionality will be implemented. Booking ID: ' + bookingId);
            // You can implement a modal or redirect to reschedule page here
        }

        // Show success/error messages
        <?php if (isset($success_message)): ?>
            showNotification('<?= $success_message ?>', 'success');
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            showNotification('<?= $error_message ?>', 'error');
        <?php endif; ?>

        // Auto-hide notifications
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success')) {
                showNotification('Booking updated successfully!', 'success');
            }
        });
    </script>
</body>
</html>