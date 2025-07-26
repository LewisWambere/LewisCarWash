<?php
session_start();
include('db_connection.php');

// Check if user is logged in and is customer
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: book_service.php");
    exit();
}

// Calculate cart totals
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}

// Handle payment processing
if (isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
    $phone_number = $_POST['phone_number'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    
    // Generate unique order ID
    $order_id = 'LCW_' . time() . '_' . rand(1000, 9999);
    
    // Store order in database
    $order_query = "INSERT INTO orders (order_id, customer_username, total_amount, payment_method, status, created_at) 
                    VALUES ('$order_id', '$username', '$cart_total', '$payment_method', 'pending', NOW())";
    
    if (mysqli_query($conn, $order_query)) {
        $order_db_id = mysqli_insert_id($conn);
        
        // Store order items
        foreach ($_SESSION['cart'] as $item) {
            $item_query = "INSERT INTO order_items (order_id, service_id, service_name, price, quantity) 
                          VALUES ('$order_db_id', '{$item['id']}', '{$item['name']}', '{$item['price']}', '{$item['quantity']}')";
            mysqli_query($conn, $item_query);
        }
        
        if ($payment_method === 'mpesa') {
            // Redirect to M-Pesa payment processing
            header("Location: ?step=mpesa_payment&order_id=" . urlencode($order_id) . "&phone=" . urlencode($phone_number));
            exit();
        } elseif ($payment_method === 'paypal') {
            // Redirect to PayPal payment processing
            header("Location: ?step=paypal_payment&order_id=" . urlencode($order_id));
            exit();
        }
    } else {
        $error_message = "Error creating order. Please try again.";
    }
}

// Handle M-Pesa STK Push
if (isset($_GET['step']) && $_GET['step'] === 'mpesa_payment') {
    $order_id = $_GET['order_id'];
    $phone_number = $_GET['phone'];
    
    // M-Pesa Daraja API integration
    $mpesa_result = initiateMpesaPayment($order_id, $phone_number, $cart_total);
}

// M-Pesa Daraja API Functions
function initiateMpesaPayment($order_id, $phone_number, $amount) {
    // M-Pesa API credentials (replace with your actual credentials)
    $consumer_key = 'YOUR_CONSUMER_KEY';
    $consumer_secret = 'YOUR_CONSUMER_SECRET';
    $business_short_code = 'YOUR_BUSINESS_SHORTCODE';
    $passkey = 'YOUR_PASSKEY';
    $callback_url = 'https://yourdomain.com/mpesa_callback.php';
    
    // Generate access token
    $access_token = getMpesaAccessToken($consumer_key, $consumer_secret);
    
    if (!$access_token) {
        return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
    }
    
    // Prepare STK Push request
    $timestamp = date('YmdHis');
    $password = base64_encode($business_short_code . $passkey . $timestamp);
    
    $stkpush_data = [
        'BusinessShortCode' => $business_short_code,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone_number,
        'PartyB' => $business_short_code,
        'PhoneNumber' => $phone_number,
        'CallBackURL' => $callback_url,
        'AccountReference' => $order_id,
        'TransactionDesc' => 'Lewis Car Wash Payment'
    ];
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkpush_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getMpesaAccessToken($consumer_key, $consumer_secret) {
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data['access_token'] : false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Lewis Car Wash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD"></script>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo-section h1 {
            font-size: 2rem;
            color: #ffd700;
            font-weight: bold;
        }

        .back-btn {
            background: linear-gradient(135deg, #666 0%, #555 100%);
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

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            color: white;
            text-decoration: none;
        }

        .main-content {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .checkout-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .checkout-title h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .order-summary, .payment-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: #ffd700;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .item-details h4 {
            color: #1a1a1a;
            margin-bottom: 0.3rem;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .item-price {
            font-weight: 600;
            color: #1a1a1a;
        }

        .payment-methods {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-option:hover {
            border-color: #ffd700;
            background: #fff9e6;
        }

        .payment-option.selected {
            border-color: #ffd700;
            background: #fff9e6;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
        }

        .payment-option input[type="radio"] {
            margin: 0;
            scale: 1.5;
        }

        .payment-icon {
            font-size: 2rem;
            width: 60px;
            text-align: center;
        }

        .mpesa-icon {
            color: #00a651;
        }

        .paypal-icon {
            color: #0070ba;
        }

        .payment-info h3 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .payment-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ffd700;
        }

        .payment-form {
            display: none;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .payment-form.active {
            display: block;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4a 100%);
            color: #1a1a1a;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        #paypal-button-container {
            margin-top: 1rem;
        }

        .mpesa-instructions {
            background: #e8f5e8;
            border: 1px solid #00a651;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .mpesa-instructions h4 {
            color: #00a651;
            margin-bottom: 0.5rem;
        }

        .mpesa-instructions ol {
            color: #2d5a3d;
            padding-left: 1.5rem;
        }

        .mpesa-instructions li {
            margin-bottom: 0.3rem;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            color: #333;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ffd700;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message, .success-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .main-content {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .checkout-title h2 {
                font-size: 2rem;
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
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Checkout Title -->
        <div class="checkout-title">
            <h2>Checkout</h2>
            <p>Complete your booking with secure payment</p>
        </div>

        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['step']) && $_GET['step'] === 'mpesa_payment'): ?>
        <!-- M-Pesa Payment Processing -->
        <div class="payment-section" style="max-width: 600px; margin: 0 auto;">
            <h3 class="section-title">
                <i class="fas fa-mobile-alt mpesa-icon"></i> M-Pesa Payment
            </h3>
            
            <div class="mpesa-instructions">
                <h4>Payment Instructions:</h4>
                <ol>
                    <li>A payment request has been sent to your phone</li>
                    <li>Check your phone for M-Pesa STK push notification</li>
                    <li>Enter your M-Pesa PIN to complete the payment</li>
                    <li>You will receive a confirmation SMS once payment is successful</li>
                </ol>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <div class="spinner"></div>
                <p>Waiting for payment confirmation...</p>
                <p><strong>Amount: KES <?= number_format($cart_total, 2) ?></strong></p>
                <p>Order ID: <?= htmlspecialchars($_GET['order_id']) ?></p>
            </div>

            <div style="margin-top: 2rem; text-align: center;">
                <button onclick="checkPaymentStatus('<?= $_GET['order_id'] ?>')" class="submit-btn" style="width: auto; padding: 0.8rem 2rem;">
                    <i class="fas fa-sync-alt"></i> Check Payment Status
                </button>
            </div>
        </div>

        <?php elseif (isset($_GET['step']) && $_GET['step'] === 'paypal_payment'): ?>
        <!-- PayPal Payment Processing -->
        <div class="payment-section" style="max-width: 600px; margin: 0 auto;">
            <h3 class="section-title">
                <i class="fab fa-paypal paypal-icon"></i> PayPal Payment
            </h3>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <p><strong>Amount: $<?= number_format($cart_total * 0.0074, 2) ?></strong> (KES <?= number_format($cart_total, 2) ?>)</p>
                <p>Order ID: <?= htmlspecialchars($_GET['order_id']) ?></p>
            </div>

            <div id="paypal-button-container"></div>
        </div>

        <?php else: ?>
        <!-- Checkout Form -->
        <div class="checkout-container">
            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="section-title">
                    <i class="fas fa-receipt"></i> Order Summary
                </h3>
                
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="order-item">
                    <div class="item-details">
                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                        <div class="item-quantity">Quantity: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="item-price">KES <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="order-item">
                    <div class="item-details">
                        <h4>Total Amount</h4>
                    </div>
                    <div class="item-price">KES <?= number_format($cart_total, 2) ?></div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="payment-section">
                <h3 class="section-title">
                    <i class="fas fa-credit-card"></i> Payment Method
                </h3>

                <form method="POST" id="checkoutForm">
                    <!-- Customer Information -->
                    <div class="form-group">
                        <label for="customer_name">Full Name</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_email">Email Address</label>
                        <input type="email" id="customer_email" name="customer_email" required>
                    </div>

                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <div class="payment-option" onclick="selectPayment('mpesa')">
                            <input type="radio" name="payment_method" value="mpesa" id="mpesa">
                            <div class="payment-icon mpesa-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-info">
                                <h3>M-Pesa</h3>
                                <p>Pay using your M-Pesa mobile money account</p>
                            </div>
                        </div>

                        <div class="payment-option" onclick="selectPayment('paypal')">
                            <input type="radio" name="payment_method" value="paypal" id="paypal">
                            <div class="payment-icon paypal-icon">
                                <i class="fab fa-paypal"></i>
                            </div>
                            <div class="payment-info">
                                <h3>PayPal</h3>
                                <p>Pay securely with your PayPal account or card</p>
                            </div>
                        </div>
                    </div>

                    <!-- M-Pesa Form -->
                    <div id="mpesa-form" class="payment-form">
                        <div class="form-group">
                            <label for="phone_number">M-Pesa Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" placeholder="254XXXXXXXXX" pattern="254[0-9]{9}">
                        </div>
                        <div class="mpesa-instructions">
                            <h4>How it works:</h4>
                            <ol>
                                <li>Enter your M-Pesa registered phone number</li>
                                <li>Click "Pay with M-Pesa" to initiate payment</li>
                                <li>Check your phone for STK push notification</li>
                                <li>Enter your M-Pesa PIN to complete payment</li>
                            </ol>
                        </div>
                    </div>

                    <!-- PayPal Form -->
                    <div id="paypal-form" class="payment-form">
                        <p>You will be redirected to PayPal to complete your payment securely.</p>
                        <p><strong>Amount: $<?= number_format($cart_total * 0.0074, 2) ?></strong> (converted from KES <?= number_format($cart_total, 2) ?>)</p>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn" disabled>
                        <i class="fas fa-lock"></i> Complete Payment
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Processing Payment...</h3>
            <p>Please do not close this window</p>
        </div>
    </div>

    <script>
        let selectedPayment = null;

        function selectPayment(method) {
            // Remove previous selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            document.querySelectorAll('.payment-form').forEach(form => {
                form.classList.remove('active');
            });

            // Add selection to clicked option
            document.querySelector(`#${method}`).closest('.payment-option').classList.add('selected');
            document.querySelector(`#${method}`).checked = true;
            document.querySelector(`#${method}-form`).classList.add('active');
            
            selectedPayment = method;
            document.getElementById('submitBtn').disabled = false;
            
            // Update submit button text
            const submitBtn = document.getElementById('submitBtn');
            if (method === 'mpesa') {
                submitBtn.innerHTML = '<i class="fas fa-mobile-alt"></i> Pay with M-Pesa';
            } else if (method === 'paypal') {
                submitBtn.innerHTML = '<i class="fab fa-paypal"></i> Pay with PayPal';
            }
        }

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (selectedPayment === 'mpesa') {
                const phoneNumber = document.getElementById('phone_number').value;
                if (!phoneNumber || !phoneNumber.match(/^254[0-9]{9}$/)) {
                    e.preventDefault();
                    alert('Please enter a valid M-Pesa phone number (format: 254XXXXXXXXX)');
                    return;
                }
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';
        });

        // PayPal Integration (for the PayPal payment step)
        <?php if (isset($_GET['step']) && $_GET['step'] === 'paypal_payment'): ?>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?= number_format($cart_total * 0.0074, 2) ?>'
                        },
                        reference_id: '<?= $_GET['order_id'] ?>'
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    // Payment successful
                    window.location.href = 'payment_complete.php?order_id=<?= $_GET['order_id'] ?>&payment_id=' + details.id;
                });
            },
            onError: function(err) {
                alert('Payment failed. Please try again.');
                window.location.href = 'checkout.php';
            }
        }).render('#paypal-button-container');
        <?php endif; ?>

        // Check M-Pesa payment status
        function checkPaymentStatus(orderId) {
            fetch('check_payment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + encodeURIComponent(orderId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.paid) {
                        window.location.href = 'payment_complete.php?order_id=' + orderId;
                    } else {
                        alert('Payment not yet received. Please complete the payment on your phone.');
                    }
                } else {
                    alert('Error checking payment status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error checking payment status');
            });
        }

        // Auto-check payment status for M-Pesa every 10 seconds
        <?php if (isset($_GET['step']) && $_GET['step'] === 'mpesa_payment'): ?>
        setInterval(function() {
            checkPaymentStatus('<?= $_GET['order_id'] ?>');
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>