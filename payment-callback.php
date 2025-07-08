<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    redirect('orders.php');
}

$paymentStatus = 'failed';
$message = 'Payment failed. Please try again.';

// Verify payment with PhonePe
if (isset($_POST['response'])) {
    $response = json_decode(base64_decode($_POST['response']), true);
    
    if ($response && isset($response['success']) && $response['success']) {
        $paymentStatus = 'paid';
        $message = 'Payment successful! Your order is now being processed.';
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Send confirmation email
        $subject = 'Payment Confirmation - Order #' . $order_id;
        $emailMessage = "Dear " . $_SESSION['username'] . ",\n\n";
        $emailMessage .= "Your payment for Order #$order_id has been successfully processed.\n";
        $emailMessage .= "Amount Paid: " . formatCurrency($order['final_total']) . "\n\n";
        $emailMessage .= "Your order is now being processed and you will receive updates on its progress.\n\n";
        $emailMessage .= "Thank you for choosing " . SITE_NAME . "!\n";
        
        sendEmailNotification($_SESSION['email'], $subject, $emailMessage);
    }
} else {
    // Handle GET callback (user cancelled or error)
    $message = 'Payment was cancelled or failed. Please try again.';
}

$_SESSION[$paymentStatus == 'paid' ? 'success' : 'error'] = $message;
redirect('orders.php');
?>