<?php
// Replace the initiateMpesaPayment function in your checkout.php with this updated version:

function initiateMpesaPayment($order_id, $phone_number, $amount, $conn) {
    // M-Pesa API credentials from your Safaricom Developer Portal
    $consumer_key = 'GWOycWDstHur5rVDlViTa97hIG442cG1NxRtuhoeJwkvMIfe';  // Your actual consumer key
    $consumer_secret = '3WzLeewiX54QBX5iwYZd3fJWchHXvzOR8D7gjvnGAT3rrjrmxwUGK8s40mhJtAes';  // Your actual consumer secret
    
    // For Sandbox Testing (Paybill)
    $business_short_code = '174379';  // Default sandbox paybill
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';  // Default sandbox passkey
    
    // For Production - Replace with your actual values
    // $business_short_code = 'YOUR_ACTUAL_PAYBILL_NUMBER';
    // $passkey = 'YOUR_ACTUAL_PASSKEY';
    
    // Update with your ngrok URL
    $callback_url = 'https://0cd04916f8be.ngrok-free.app/mpesa_callback.php';  // Your ngrok URL
    
    // Use sandbox URLs for testing, production URLs for live
    $is_live = false; // Set to true for production
    
    if ($is_live) {
        $oauth_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $stkpush_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    } else {
        $oauth_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $stkpush_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }
    
    // Generate access token
    $access_token = getMpesaAccessToken($consumer_key, $consumer_secret, $oauth_url);
    
    if (!$access_token) {
        return ['success' => false, 'message' => 'Failed to get M-Pesa access token'];
    }
    
    // Format phone number (ensure it starts with 254)
    $phone_number = formatPhoneNumber($phone_number);
    if (!$phone_number) {
        return ['success' => false, 'message' => 'Invalid phone number format'];
    }
    
    // Prepare STK Push request
    $timestamp = date('YmdHis');
    $password = base64_encode($business_short_code . $passkey . $timestamp);
    
    $stkpush_data = [
        'BusinessShortCode' => $business_short_code,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int)$amount, // Ensure it's an integer
        'PartyA' => $phone_number,
        'PartyB' => $business_short_code,
        'PhoneNumber' => $phone_number,
        'CallBackURL' => $callback_url,
        'AccountReference' => $order_id,
        'TransactionDesc' => 'Lewis Car Wash Payment - ' . $order_id
    ];
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $stkpush_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkpush_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Enhanced logging for debugging
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'order_id' => $order_id,
        'phone_number' => $phone_number,
        'amount' => $amount,
        'request_data' => $stkpush_data,
        'response' => $response,
        'http_code' => $http_code,
        'curl_error' => $curl_error
    ];
    file_put_contents('mpesa_stk_log.txt', json_encode($log_data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    if ($curl_error) {
        return ['success' => false, 'message' => 'Connection error: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        return ['success' => false, 'message' => 'HTTP error: ' . $http_code . ' - Response: ' . $response];
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        return ['success' => false, 'message' => 'Invalid response from M-Pesa'];
    }
    
    // Check for successful response
    if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
        return [
            'success' => true,
            'CheckoutRequestID' => $data['CheckoutRequestID'],
            'MerchantRequestID' => $data['MerchantRequestID'],
            'ResponseDescription' => $data['ResponseDescription']
        ];
    } else {
        // Handle various error scenarios
        $error_message = '';
        
        if (isset($data['ResponseDescription'])) {
            $error_message = $data['ResponseDescription'];
        } elseif (isset($data['errorMessage'])) {
            $error_message = $data['errorMessage'];
        } elseif (isset($data['RequestId']) && isset($data['errorCode'])) {
            $error_message = "Error " . $data['errorCode'] . ": " . ($data['errorMessage'] ?? 'Unknown error');
        } else {
            $error_message = 'Unknown error occurred';
        }
        
        return ['success' => false, 'message' => $error_message];
    }
}

// Enhanced access token function with better error handling
function getMpesaAccessToken($consumer_key, $consumer_secret, $oauth_url) {
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $oauth_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log OAuth attempts
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'oauth_url' => $oauth_url,
        'http_code' => $http_code,
        'response' => $response,
        'curl_error' => $curl_error
    ];
    file_put_contents('mpesa_oauth_log.txt', json_encode($log_data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    
    if ($curl_error) {
        error_log("M-Pesa OAuth cURL Error: " . $curl_error);
        return false;
    }
    
    if ($http_code !== 200) {
        error_log("M-Pesa OAuth HTTP Error: " . $http_code . " - " . $response);
        return false;
    }
    
    $data = json_decode($response, true);
    return isset($data['access_token']) ? $data['access_token'] : false;
}

// Enhanced phone number formatting with better validation
function formatPhoneNumber($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle different formats
    if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        // Convert 0XXXXXXXXX to 254XXXXXXXXX
        $formatted = '254' . substr($phone, 1);
    } elseif (strlen($phone) == 9) {
        // Convert XXXXXXXXX to 254XXXXXXXXX
        $formatted = '254' . $phone;
    } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
        // Already in correct format
        $formatted = $phone;
    } else {
        return false; // Invalid format
    }
    
    // Validate Kenyan mobile number format (should start with 254 followed by 7, 1, or 0)
    if (preg_match('/^254[701]\d{8}$/', $formatted)) {
        return $formatted;
    }
    
    return false; // Invalid Kenyan mobile number
}
?>