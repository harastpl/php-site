<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'pending'");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = 'Order not found or already paid.';
    redirect('orders.php');
}

// PhonePe Payment Integration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $merchantTransactionId = 'TXN' . time() . rand(1000, 9999);
    $amount = $order['final_total'] * 100; // Convert to paise
    
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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #e0e0ff;
            min-height: 100vh;
        }
        .payment-card {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            margin: 50px auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="payment-card">
            <div class="text-center mb-4">
                <h2 class="glow">Payment</h2>
                <p class="text-muted">Order #<?php echo $order['id']; ?></p>
            </div>
            
            <div class="order-summary mb-4">
                <h5>Order Summary</h5>
                <div class="d-flex justify-content-between">
                    <span>Subtotal:</span>
                    <span><?php echo formatCurrency($order['total']); ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="d-flex justify-content-between text-success">
                        <span>Discount:</span>
                        <span>-<?php echo formatCurrency($order['discount_amount']); ?></span>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between h5">
                    <span>Total:</span>
                    <span><?php echo formatCurrency($order['final_total']); ?></span>
                </div>
            </div>
            
            <form method="post">
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    Pay with PhonePe
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>