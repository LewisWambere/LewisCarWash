<?php
// mpesa_callback.php - Handle M-Pesa payment callbacks
header('Content-Type: application/json');
include('db_connection.php');

// Log callback data for debugging
$log_file = 'mpesa_callbacks.log';
$timestamp = date('Y-m-d H:i:s');
$callback_data = file_get_contents('php://input');

// Log the raw callback data
file_put_contents($log_file, "[$timestamp] Raw Callback: $callback_data\n", FILE_APPEND);

$callback_array = json_decode($callback_data, true);

if (!$callback_array) {
    file_put_contents($log_file, "[$timestamp] ERROR: Invalid JSON received\n", FILE_APPEND);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid JSON']);
    exit;
}

try {
    // Extract callback data
    $result_code = $callback_array['Body']['stkCallback']['ResultCode'] ?? null;
    $result_desc = $callback_array['Body']['stkCallback']['ResultDesc'] ?? '';
    $merchant_request_id = $callback_array['Body']['stkCallback']['MerchantRequestID'] ?? '';
    $checkout_request_id = $callback_array['Body']['stkCallback']['CheckoutRequestID'] ?? '';
    
    file_put_contents($log_file, "[$timestamp] Processing callback - Result Code: $result_code, Desc: $result_desc\n", FILE_APPEND);
    
    if ($result_code == 0) {
        // Payment successful
        $callback_metadata = $callback_array['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
        
        $amount = 0;
        $mpesa_receipt_number = '';
        $phone_number = '';
        $transaction_date = '';
        $account_reference = '';
        
        // Extract metadata
        foreach ($callback_metadata as $item) {
            switch ($item['Name']) {
                case 'Amount':
                    $amount = $item['Value'];
                    break;
                case 'MpesaReceiptNumber':
                    $mpesa_receipt_number = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phone_number = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transaction_date = $item['Value'];
                    break;
                case 'AccountReference':
                    $account_reference = $item['Value'];
                    break;
            }
        }
        
        // Update order status in database
        $update_query = "UPDATE orders SET 
                        status = 'completed',
                        payment_reference = '$mpesa_receipt_number',
                        payment_phone = '$phone_number',
                        paid_amount = '$amount',
                        payment_date = NOW(),
                        checkout_request_id = '$checkout_request_id'
                        WHERE order_id = '$account_reference'";
        
        if (mysqli_query($conn, $update_query)) {
            file_put_contents($log_file, "[$timestamp] SUCCESS: Order $account_reference updated successfully\n", FILE_APPEND);
            
            // Create transaction record
            $transaction_query = "INSERT INTO transactions (
                order_id, customer_username, amount, payment_method, 
                payment_reference, status, created_at
            ) SELECT 
                order_id, customer_username, total_amount, 'mpesa',
                '$mpesa_receipt_number', 'completed', NOW()
            FROM orders WHERE order_id = '$account_reference'";
            
            mysqli_query($conn, $transaction_query);
            
        } else {
            file_put_contents($log_file, "[$timestamp] ERROR: Database update failed for order $account_reference\n", FILE_APPEND);
        }
        
    } else {
        // Payment failed
        $update_query = "UPDATE orders SET 
                        status = 'failed',
                        failure_reason = '$result_desc',
                        checkout_request_id = '$checkout_request_id'
                        WHERE checkout_request_id = '$checkout_request_id'";
        
        mysqli_query($conn, $update_query);
        file_put_contents($log_file, "[$timestamp] FAILED: Payment failed - $result_desc\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    file_put_contents($log_file, "[$timestamp] EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Respond to Safaricom
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
?>