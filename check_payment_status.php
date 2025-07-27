<?php
// check_payment_status.php - Check payment status for orders
session_start();
include('db_connection.php');

header('Content-Type: application/json');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'payment_status_errors.log');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required parameters
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);

// Validate user session for security
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$username = $_SESSION['username'];

try {
    // Check order status with user validation
    $status_query = "SELECT 
                        o.*,
                        GROUP_CONCAT(oi.service_name SEPARATOR ', ') as service_names
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.id = oi.order_id
                     WHERE o.order_id = '$order_id' 
                     AND o.customer_username = '$username'
                     GROUP BY o.id";
    
    $result = mysqli_query($conn, $status_query);

    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    $order = mysqli_fetch_assoc($result);

    // Prepare base response
    $response = [
        'success' => true,
        'order_id' => $order['order_id'],
        'status' => $order['status'],
        'paid' => in_array($order['status'], ['completed', 'confirmed']),
        'amount' => floatval($order['total_amount']),
        'payment_method' => $order['payment_method'],
        'service_names' => $order['service_names'] ?? 'N/A',
        'created_at' => $order['created_at']
    ];

    // Add status-specific information
    switch ($order['status']) {
        case 'completed':
            $response['payment_reference'] = $order['payment_reference'] ?? '';
            $response['payment_date'] = $order['payment_date'] ?? $order['created_at'];
            $response['payment_phone'] = $order['payment_phone'] ?? '';
            $response['paid_amount'] = floatval($order['paid_amount'] ?? $order['total_amount']);
            
            // Format payment date for display
            if ($response['payment_date']) {
                $response['payment_date_formatted'] = date('M j, Y g:i A', strtotime($response['payment_date']));
            }
            break;
            
        case 'failed':
            $response['failure_reason'] = $order['failure_reason'] ?? 'Payment failed';
            $response['failed_at'] = $order['updated_at'] ?? $order['created_at'];
            break;
            
        case 'pending':
            // Check if payment is taking too long (more than 10 minutes)
            $created_time = strtotime($order['created_at']);
            $current_time = time();
            $time_diff = $current_time - $created_time;
            
            if ($time_diff > 600) { // 10 minutes
                $response['timeout_warning'] = true;
                $response['timeout_message'] = 'Payment is taking longer than expected. Please try again or contact support.';
            }
            
            // Add checkout request ID if available (for M-Pesa tracking)
            if (!empty($order['checkout_request_id'])) {
                $response['checkout_request_id'] = $order['checkout_request_id'];
            }
            break;
            
        case 'confirmed':
            $response['confirmed_at'] = $order['updated_at'] ?? $order['created_at'];
            break;
    }

    // Add additional order details
    $response['customer_name'] = $order['customer_name'] ?? '';
    $response['customer_email'] = $order['customer_email'] ?? '';

    // Log the status check for monitoring
    $log_entry = date('Y-m-d H:i:s') . " - Status check for order $order_id: {$order['status']} by user $username\n";
    file_put_contents('payment_status_checks.log', $log_entry, FILE_APPEND | LOCK_EX);

    // If the order is still pending and it's M-Pesa, optionally query M-Pesa status
    if ($order['status'] === 'pending' && $order['payment_method'] === 'mpesa' && !empty($order['checkout_request_id'])) {
        // Only check M-Pesa status if the order is recent (within last 15 minutes)
        $created_time = strtotime($order['created_at']);
        $current_time = time();
        $time_diff = $current_time - $created_time;
        
        if ($time_diff <= 900) { // 15 minutes
            $mpesa_status = checkMpesaTransactionStatus($order['checkout_request_id']);
            if ($mpesa_status) {
                $response['mpesa_status'] = $mpesa_status;
            }
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Payment status check error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while checking payment status'
    ]);
}

/**
 * Optional function to check M-Pesa transaction status directly
 * This requires additional M-Pesa API integration
 */
function checkMpesaTransactionStatus($checkout_request_id) {
    // This is optional - you can implement direct M-Pesa status checking here
    // For now, we'll rely on the callback system
    return null;
    
    /*
    // Example implementation:
    try {
        $consumer_key = 'YOUR_CONSUMER_KEY';
        $consumer_secret = 'YOUR_CONSUMER_SECRET';
        $business_short_code = 'YOUR_BUSINESS_SHORTCODE';
        $passkey = 'YOUR_PASSKEY';
        
        // Get access token
        $access_token = getMpesaAccessToken($consumer_key, $consumer_secret);
        if (!$access_token) {
            return null;
        }
        
        // Prepare query request
        $timestamp = date('YmdHis');
        $password = base64_encode($business_short_code . $passkey . $timestamp);
        
        $query_data = [
            'BusinessShortCode' => $business_short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkout_request_id
        ];
        
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($data && isset($data['ResponseCode'])) {
            return [
                'response_code' => $data['ResponseCode'],
                'response_description' => $data['ResponseDescription'] ?? '',
                'result_code' => $data['ResultCode'] ?? '',
                'result_desc' => $data['ResultDesc'] ?? ''
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("M-Pesa status query error: " . $e->getMessage());
        return null;
    }
    */
}

/**
 * Helper function to get M-Pesa access token
 */
function getMpesaAccessToken($consumer_key, $consumer_secret) {
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data['access_token'] : false;
}
?>