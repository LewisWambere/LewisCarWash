<?php
// payment_complete.php - Payment success confirmation page
session_start();
include('db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$order_id = $_GET['order_id'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';

if (empty($order_id)) {
    header("Location: book_service.php");
    exit();
}

// Get order details
$order_query = "SELECT * FROM orders WHERE order_id = '$order_id' AND customer_username = '$username'";
$order_result = mysqli_query($conn, $order_query);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    header("Location: book_service.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = '{$order['id']}'";
$items_result = mysqli_query($conn, $items_query);

// Clear cart after successful payment
if ($order['status'] === 'completed') {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Complete - Lewis Car Wash</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 600px;
            width: 90%;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 2rem;
            animation: bounceIn 0.8s ease-out 0.2s both;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-title {
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .success-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .order-details {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .order-details h3 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            background: #fff;
            margin: 1rem -1rem -1rem;
            padding: 1rem;
            border-radius: 0 0 8px 8px;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
        }

        .detail-value {
            color: #1a1a1a;
            font-weight: 600;
        }

        .payment-info {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .payment-info h4 {
            color: #28a745;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-info p {
            color: #2d5a3d;
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 180px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-decoration: none;
        }

        .btn-primary:hover {
            color: #1a1a1a;
        }

        .btn-secondary:hover {
            color: white;
        }

        .print-btn {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            margin-top: 1rem;
        }

        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ffd700;
        }

        .logo-header h1 {
            color: #ffd700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .logo-header p {
            color: #666;
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 2rem;
                margin: 1rem;
            }

            .success-title {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: auto;
                width: 100%;
            }
        }

        @media print {
            body {
                background: white;
                color: black;
            }

            .action-buttons {
                display: none;
            }

            .success-container {
                box-shadow: none;
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="success-container" id="receiptContainer">
        <div class="logo-header">
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
            <p>Professional Car Cleaning Services</p>
        </div>

        <?php if ($order['status'] === 'completed'): ?>
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>

        <h1 class="success-title">Payment Successful!</h1>
        <p class="success-message">
            Thank you for choosing Lewis Car Wash! Your booking has been confirmed and payment received successfully.
        </p>

        <div class="payment-info">
            <h4>
                <i class="fas fa-info-circle"></i>
                Payment Confirmation
            </h4>
            <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
            <?php if ($order['payment_reference']): ?>
            <p><strong>Payment Reference:</strong> <?= htmlspecialchars($order['payment_reference']) ?></p>
            <?php endif; ?>
            <?php if ($payment_id): ?>
            <p><strong>Transaction ID:</strong> <?= htmlspecialchars($payment_id) ?></p>
            <?php endif; ?>
            <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?></p>
            <p><strong>Amount Paid:</strong> KES <?= number_format($order['total_amount'], 2) ?></p>
            <p><strong>Payment Date:</strong> <?= date('M d, Y - H:i', strtotime($order['payment_date'] ?? $order['created_at'])) ?></p>
        </div>

        <div class="order-details">
            <h3><i class="fas fa-receipt"></i> Order Details</h3>
            
            <?php if ($items_result && mysqli_num_rows($items_result) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                <div class="detail-row">
                    <div class="detail-label">
                        <?= htmlspecialchars($item['service_name']) ?> 
                        <small>(Qty: <?= $item['quantity'] ?>)</small>
                    </div>
                    <div class="detail-value">KES <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
            
            <div class="detail-row">
                <div class="detail-label">Total Amount</div>
                <div class="detail-value">KES <?= number_format($order['total_amount'], 2) ?></div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="book_service.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Book More Services
            </a>
            <a href="my_bookings.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> View My Bookings
            </a>
        </div>

        <button onclick="printReceipt()" class="btn print-btn">
            <i class="fas fa-print"></i> Print Receipt
        </button>

        <?php else: ?>
        <!-- Payment Failed -->
        <div class="success-icon" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
            <i class="fas fa-times"></i>
        </div>

        <h1 class="success-title">Payment Failed</h1>
        <p class="success-message">
            Unfortunately, your payment could not be processed. Please try again.
        </p>

        <div class="order-details">
            <h3><i class="fas fa-exclamation-triangle"></i> Order Information</h3>
            <div class="detail-row">
                <div class="detail-label">Order ID</div>
                <div class="detail-value"><?= htmlspecialchars($order['order_id']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value" style="color: #dc3545;">Failed</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Amount</div>
                <div class="detail-value">KES <?= number_format($order['total_amount'], 2) ?></div>
            </div>
            <?php if ($order['failure_reason']): ?>
            <div class="detail-row">
                <div class="detail-label">Reason</div>
                <div class="detail-value"><?= htmlspecialchars($order['failure_reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="checkout.php" class="btn btn-primary">
                <i class="fas fa-redo"></i> Try Again
            </a>
            <a href="book_service.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function printReceipt() {
            window.print();
        }

        // Auto-redirect to dashboard after 30 seconds for successful payments
        <?php if ($order['status'] === 'completed'): ?>
        setTimeout(function() {
            if (confirm('Would you like to return to the dashboard?')) {
                window.location.href = 'book_service.php';
            }
        }, 30000);
        <?php endif; ?>

        // Prevent back button after successful payment
        <?php if ($order['status'] === 'completed'): ?>
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        <?php endif; ?>
    </script>
</body>
</html>