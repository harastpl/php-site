<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$order_id = (int)($_GET['order_id'] ?? 0);

if (!$order_id) {
    $_SESSION['error'] = 'Invalid callback request.';
    redirect('orders.php');
    exit();
}

// Fetch the order to get the merchantOrderId (stored in payment_id)
$stmt = $pdo->prepare("SELECT payment_id FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order || empty($order['payment_id'])) {
    $_SESSION['error'] = 'Could not find the transaction to verify.';
    redirect('orders.php');
    exit();
}

$merchantOrderId = $order['payment_id'];

// Get a new access token to make an authenticated API call
include 'includes/phonepe_v2_auth.php';

// Check the status of the transaction
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => PHONEPE_BASE_URL . '/checkout/v2/status/' . $merchantOrderId,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: O-Bearer ' . $accessToken
  ),
));

$response = curl_exec($curl);
curl_close($curl);
$statusInfo = json_decode($response, true);

if (isset($statusInfo['paymentState']) && $statusInfo['paymentState'] === 'COMPLETED') {
    // Payment is successful, update your database
    $stmt_update = $pdo->prepare("UPDATE orders SET status = 'processing', payment_status = 'paid' WHERE id = ?");
    $stmt_update->execute([$order_id]);

    $_SESSION['success'] = 'Payment successful! Your order #' . $order_id . ' is now being processed.';
    redirect('orders.php');
} else {
    // Payment failed or is in another state
    $stmt_update = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?");
    $stmt_update->execute([$order_id]);

    $_SESSION['error'] = 'Payment failed for order #' . $order_id . '. Please try again or contact support.';
    redirect('orders.php');
}
?>