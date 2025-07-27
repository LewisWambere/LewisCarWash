<?php
session_start();
include('db_connection.php');

// Check login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];

// Handle cancel booking action
if (isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['cancel']);
    $cancel_query = "UPDATE orders 
                     SET status='cancelled' 
                     WHERE order_id='$order_id' 
                     AND customer_username='$username' 
                     AND status IN ('pending','confirmed')";
    mysqli_query($conn, $cancel_query);
    header("Location: my_bookings.php");
    exit();
}

// Fetch bookings
$bookings_query = "SELECT * FROM orders WHERE customer_username='$username' ORDER BY created_at DESC";
$bookings_result = mysqli_query($conn, $bookings_query);

// Fetch stats
$stats_query = "SELECT 
                    COUNT(*) AS total_bookings,
                    COUNT(CASE WHEN status='completed' THEN 1 END) AS completed_bookings,
                    COUNT(CASE WHEN status='pending' THEN 1 END) AS pending_bookings,
                    COUNT(CASE WHEN status='confirmed' THEN 1 END) AS confirmed_bookings,
                    COUNT(CASE WHEN status='cancelled' THEN 1 END) AS cancelled_bookings,
                    COALESCE(SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END),0) AS total_spent
                FROM orders
                WHERE customer_username='$username'";
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
            background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%);
            color: #ffffff;
            min-height: 100vh;
            padding: 2rem;
            line-height: 1.6;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #ffd700, transparent);
            z-index: -1;
        }

        .header h1 {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 8px rgba(255, 215, 0, 0.3);
            display: inline-block;
            background-color: #1a1a1a;
            padding: 0 2rem;
        }

        .header p {
            color: #cccccc;
            font-size: 1.1rem;
            font-weight: 300;
        }

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
            border: none;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffed4a 0%, #ffd700 100%);
        }

        .btn-back:active {
            transform: translateY(0);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ffd700, #ffed4a);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.4);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
        }

        .stat-label {
            font-size: 1rem;
            color: #cccccc;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            color: rgba(255, 215, 0, 0.3);
        }

        /* Table Section */
        .bookings-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 2rem;
            overflow: hidden;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #ffd700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(42, 42, 42, 0.8);
            backdrop-filter: blur(10px);
        }

        th {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1.5rem 1rem;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        th:first-child {
            border-top-left-radius: 15px;
        }

        th:last-child {
            border-top-right-radius: 15px;
        }

        td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-weight: 500;
        }

        tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:last-child td:first-child {
            border-bottom-left-radius: 15px;
        }

        tr:last-child td:last-child {
            border-bottom-right-radius: 15px;
        }

        /* Status Badges */
        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.5px;
            min-width: 100px;
            justify-content: center;
        }

        .status.pending {
            background: linear-gradient(135deg, #ff9500 0%, #ffb347 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(255, 149, 0, 0.3);
        }

        .status.confirmed {
            background: linear-gradient(135deg, #007bff 0%, #40a9ff 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .status.completed {
            background: linear-gradient(135deg, #28a745 0%, #5cb85c 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .status.cancelled {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        /* Action Buttons */
        .btn-cancel {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b7a 100%);
            color: #ffffff;
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
        }

        /* Empty State */
        .no-bookings {
            text-align: center;
            padding: 4rem 2rem;
            color: #cccccc;
        }

        .no-bookings i {
            font-size: 4rem;
            color: rgba(255, 215, 0, 0.3);
            margin-bottom: 1rem;
        }

        .no-bookings h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .no-bookings p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-book-now {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header h1 {
                font-size: 2rem;
                padding: 0 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .bookings-section {
                padding: 1rem;
            }

            th, td {
                padding: 1rem 0.5rem;
                font-size: 0.85rem;
            }

            .btn-back {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            th, td {
                padding: 0.8rem 0.3rem;
                font-size: 0.8rem;
            }

            .status {
                min-width: 80px;
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }

            .btn-cancel {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }

        .bookings-section {
            animation: fadeInUp 0.6s ease forwards;
            animation-delay: 0.7s;
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="customer_dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-calendar-check"></i> My Bookings</h1>
            <p>Track and manage all your car wash appointments</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-clipboard-list stat-icon"></i>
                <div class="stat-number"><?= $stats['total_bookings'] ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-number"><?= $stats['completed_bookings'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-number"><?= $stats['pending_bookings'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-thumbs-up stat-icon"></i>
                <div class="stat-number"><?= $stats['confirmed_bookings'] ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle stat-icon"></i>
                <div class="stat-number"><?= $stats['cancelled_bookings'] ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave stat-icon"></i>
                <div class="stat-number">KES <?= number_format($stats['total_spent'], 0) ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>

        <!-- Bookings Section -->
        <div class="bookings-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Booking History
            </h2>

            <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Order ID</th>
                                <th><i class="fas fa-money-bill"></i> Amount</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-credit-card"></i> Payment Method</th>
                                <th><i class="fas fa-calendar"></i> Date</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($bookings_result)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['order_id']) ?></strong></td>
                                <td><strong>KES <?= number_format($row['total_amount'], 2) ?></strong></td>
                                <td>
                                    <span class="status <?= $row['status'] ?>">
                                        <?php
                                        $status_icons = [
                                            'pending' => 'fas fa-clock',
                                            'confirmed' => 'fas fa-thumbs-up',
                                            'completed' => 'fas fa-check-circle',
                                            'cancelled' => 'fas fa-times-circle'
                                        ];
                                        ?>
                                        <i class="<?= $status_icons[$row['status']] ?? 'fas fa-question' ?>"></i>
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= ucfirst($row['payment_method']) ?></td>
                                <td><?= $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : 'N/A' ?></td>
                                <td>
                                    <?php if (in_array($row['status'], ['pending', 'confirmed'])): ?>
                                        <a class="btn-cancel" 
                                           href="my_bookings.php?cancel=<?= $row['order_id'] ?>" 
                                           onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #666; font-style: italic;">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings yet. Book your first car wash service and start enjoying our premium care!</p>
                    <a href="booking.php" class="btn-book-now">
                        <i class="fas fa-plus"></i>
                        Book Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation for table rows
        document.querySelectorAll('tbody tr').forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, 100 * index);
        });

        // Confirm cancellation with better UX
        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const orderId = this.href.split('cancel=')[1];
                
                if (confirm(`Are you sure you want to cancel booking #${orderId}?\n\nThis action cannot be undone.`)) {
                    // Add loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
                    this.style.pointerEvents = 'none';
                    
                    // Navigate after short delay
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 500);
                }
            });
        });

        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-5px) scale(1)';
            });
        });
    </script>
</body>
</html>