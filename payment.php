<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Use $_REQUEST to handle both GET (initial load) and POST (form submission)
$order_id = (int)($_REQUEST['order_id'] ?? 0);
$product_id = (int)($_REQUEST['product_id'] ?? 0);
$quantity = (int)($_REQUEST['quantity'] ?? 1);
$total_amount = 0;
$shipping_total = 0;
$subtotal = 0;
$redirectTokenUrl = '';
$merchantOrderId = '';
$order = null;

// Get order details first if order_id is provided
if ($order_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    if ($order && $order['final_total'] > 0) {
        $total_amount = $order['final_total'];
        $subtotal = $order['total'];
        $shipping_total = $order['shipping_total'];
    } elseif ($order && $order['final_total'] == 0) {
        $_SESSION['error'] = 'Order price is not yet updated by admin.';
        redirect('orders.php');
    }
} elseif ($product_id) {
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if ($product) {
        $total_amount = $product['price'] * $quantity;
        $subtotal = $total_amount;
    }
}

// This block only runs when the "Pay Now" button is clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If it's a "Buy Now" link, the order must be created first
    if ($product_id && !$order_id) {
        try {
            $stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt_product->execute([$product_id]);
            $product = $stmt_product->fetch();
            if ($product) {
                $subtotal = $product['price'] * $quantity;
                
                // Get delivery charge setting
                $stmt_setting = $pdo->query("SELECT setting_value FROM settings WHERE setting_name = 'delivery_charges_enabled'");
                $delivery_charges_enabled = $stmt_setting->fetchColumn() === '1';
                
                if ($delivery_charges_enabled) {
                    if ($product['delivery_charge_threshold'] > 0 && $quantity >= $product['delivery_charge_threshold']) {
                        $shipping_total = $product['delivery_charge_alt'];
                    } else {
                        $shipping_total = $product['delivery_charge'];
                    }
                }

                $total_amount = $subtotal + $shipping_total;

                $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total, shipping_total, final_total, status, payment_status) VALUES (?, ?, ?, ?, 'pending', 'pending')");
                $stmt_order->execute([$_SESSION['user_id'], $subtotal, $shipping_total, $total_amount]);
                $order_id = $pdo->lastInsertId(); // Get the newly created order ID

                $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt_item->execute([$order_id, $product_id, $quantity, $product['price']]);
            }
        } catch (PDOException $e) {
            die('Error creating order: ' . $e->getMessage());
        }
    }
    
    // Refresh order details after potential creation
    if ($order_id && !$order) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        if ($order) {
            $total_amount = $order['final_total'];
        }
    }

    // Initiate payment with PhonePe
    include 'includes/phonepe_v2_auth.php'; // This should provide the $accessToken
    
    // Generate a unique Merchant Order ID with a readable timestamp
    $merchantOrderId = 'ORD' . $order_id . 'D' . date('YmdHis');
    $amountInPaisa = (int)($total_amount * 100);

    $payload = [
        'merchantOrderId' => $merchantOrderId,
        'amount' => $amountInPaisa,
        'expireAfter' => 1200,
        'paymentFlow' => [
            'type' => 'PG_CHECKOUT',
            'merchantUrls' => [
                'redirectUrl' => SITE_URL . '/payment-callback.php?moid=' . $merchantOrderId
            ]
        ]
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.phonepe.com/apis/pg' . '/checkout/v2/pay',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: O-Bearer ' . $accessToken
      ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $getPaymentInfo = json_decode($response, true);

    if (isset($getPaymentInfo['redirectUrl'])) {
        // Store the generated merchantOrderId in the 'payment_id' column
        $stmt = $pdo->prepare("UPDATE orders SET payment_id = ? WHERE id = ?");
        $stmt->execute([$merchantOrderId, $order_id]);
        $redirectTokenUrl = $getPaymentInfo['redirectUrl'];
    } else {
        $_SESSION['error'] = 'Gateway Error: ' . ($getPaymentInfo['error'] ?? 'Could not initiate payment.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PhonePe Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://mercury.phonepe.com/web/bundle/checkout.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <div class="card shadow-lg">
                    <div class="card-header text-center"><h4>Payment</h4></div>
                    <div class="card-body">
                        <div class="order-summary mb-4">
                            <h5>Order Summary</h5>
                            <p>Order ID: #<?php echo htmlspecialchars($order_id); ?></p>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Shipping:</span>
                                <span><?php echo $shipping_total > 0 ? formatCurrency($shipping_total) : 'Free'; ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between h5">
                                <span>Total to Pay:</span>
                                <span><?php echo formatCurrency($total_amount); ?></span>
                            </div>
                        </div>
                        <form method="post">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                            <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="payButton">Pay Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>

    <?php if (!empty($redirectTokenUrl)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tokenUrl = '<?php echo $redirectTokenUrl; ?>';
            var moid = '<?php echo $merchantOrderId; ?>';

            function paymentCallback(response) {
                window.location.href = 'payment-callback.php?moid=' + moid;
            }

            if (window.PhonePeCheckout && window.PhonePeCheckout.transact) {
                document.getElementById('payButton').innerText = 'Opening Payment Window...';
                document.getElementById('payButton').disabled = true;

                window.PhonePeCheckout.transact({
                    tokenUrl: tokenUrl,
                    callback: paymentCallback,
                    type: 'IFRAME'
                });
            } else {
                alert('PhonePeCheckout library could not be loaded.');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>