<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    redirect('orders.php');
}

// Check if order is ready for payment
if ($order['status'] != 'processing' || $order['payment_status'] != 'pending' || !$order['admin_price']) {
    $_SESSION['error'] = 'This order is not ready for payment.';
    redirect('orders.php');
}

// PhonePe Payment Integration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $merchantTransactionId = 'TXN' . time() . rand(1000, 9999);
    $amount = ($order['admin_price'] ?? $order['final_total']) * 100; // Convert to paise
    
    $paymentData = [
        'merchantId' => PHONEPE_MERCHANT_ID,
        'merchantTransactionId' => $merchantTransactionId,
        'merchantUserId' => 'USER' . $_SESSION['user_id'],
        'amount' => $amount,
        'redirectUrl' => SITE_URL . '/payment-callback.php?order_id=' . $order_id,
        'redirectMode' => 'POST',
        'callbackUrl' => SITE_URL . '/payment-callback.php?order_id=' . $order_id,
        'paymentInstrument' => [
            'type' => 'PAY_PAGE'
        ]
    ];
    
    $jsonData = json_encode($paymentData);
    $base64Data = base64_encode($jsonData);
    $checksum = hash('sha256', $base64Data . '/pg/v1/pay' . PHONEPE_SALT_KEY) . '###' . PHONEPE_SALT_INDEX;
    
    // Update order with payment ID
    $stmt = $pdo->prepare("UPDATE orders SET payment_id = ? WHERE id = ?");
    $stmt->execute([$merchantTransactionId, $order_id]);
    
    // Redirect to PhonePe
    $phonepeUrl = PHONEPE_BASE_URL . '/pg/v1/pay';
    
    echo '<form id="phonepe-form" method="POST" action="' . $phonepeUrl . '">
            <input type="hidden" name="request" value="' . $base64Data . '">
            <input type="hidden" name="checksum" value="' . $checksum . '">
          </form>
          <script>document.getElementById("phonepe-form").submit();</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <h4>Payment</h4>
                        <p class="text-muted mb-0">Order #<?php echo $order['id']; ?></p>
                    </div>
                    <div class="card-body">
                        <div class="order-summary mb-4">
                            <h5>Order Summary</h5>
                            <div class="d-flex justify-content-between">
                                <span>Original Amount:</span>
                                <span><?php echo formatCurrency($order['total']); ?></span>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between text-success">
                                    <span>Discount:</span>
                                    <span>-<?php echo formatCurrency($order['discount_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['admin_price']): ?>
                                <div class="d-flex justify-content-between">
                                    <span>Final Price (Admin Set):</span>
                                    <span><?php echo formatCurrency($order['admin_price']); ?></span>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between h5">
                                <span>Total to Pay:</span>
                                <span><?php echo formatCurrency($order['admin_price'] ?? $order['final_total']); ?></span>
                            </div>
                        </div>
                        
                        <form method="post">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Pay with PhonePe
                                </button>
                                <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                            </div>
                        </form>
                        
                        <div class="alert alert-info mt-3">
                            <small><strong>Note:</strong> You will be redirected to PhonePe for secure payment processing.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>