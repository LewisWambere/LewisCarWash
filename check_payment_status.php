<?php
// check_payment_status.php - Check payment status for orders
session_start();
include('db_connection.php');

header('Content-Type: application/json');

if (!isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$order_id = mysqli_real_escape_string($conn, $_POST['order_id']);

// Check order status
$status_query = "SELECT * FROM orders WHERE order_id = '$order_id'";
$result = mysqli_query($conn, $status_query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = mysqli_fetch_assoc($result);

$response = [
    'success' => true,
    'order_id' => $order['order_id'],
    'status' => $order['status'],
    'paid' => $order['status'] === 'completed',
    'amount' => $order['total_amount'],
    'payment_method' => $order['payment_method']
];

if ($order['status'] === 'completed') {
    $response['payment_reference'] = $order['payment_reference'];
    $response['payment_date'] = $order['payment_date'];
} elseif ($order['status'] === 'failed') {
    $response['failure_reason'] = $order['failure_reason'];
}

echo json_encode($response);
?>