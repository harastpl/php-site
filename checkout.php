<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email_functions.php';

requireLogin();

// Check if user has address
$stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$address = null;
if (!empty($user['address'])) {
    $address = json_decode($user['address'], true);
}

// Get cart items
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_unique(array_column($_SESSION['cart'], 'product_id'));
    
    if(!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Manually create an associative array with product ID as the key
        $products = [];
        foreach($products_db as $p) {
            $products[$p['id']] = $p;
        }

        foreach ($_SESSION['cart'] as $key => $item) {
            if (isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;
                
                $cart_items[] = [
                    'key' => $key,
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'custom_text' => $item['custom_text'],
                    'custom_file' => $item['custom_file']
                ];
            }
        }
    }
}

if (empty($cart_items)) {
    $_SESSION['error'] = 'No items to checkout.';
    redirect('cart.php');
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    if (!$address) {
        $_SESSION['error'] = 'Please add your delivery address first.';
        redirect('address-form.php?redirect=' . urlencode('checkout.php'));
    }
    
    try {
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, final_total, status, payment_status) VALUES (?, ?, ?, 'processing', 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Add order items with custom fields
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items 
                (order_id, product_id, quantity, price, custom_text, custom_file_upload) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, 
                $item['product']['id'], 
                $item['quantity'], 
                $item['product']['price'],
                $item['custom_text'],
                $item['custom_file']
            ]);
        }
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Send order confirmation email
        $orderDetails = [
            'status' => 'processing',
            'final_total' => $total
        ];
        sendOrderConfirmationEmail($_SESSION['email'], $order_id, $orderDetails);
        
        $_SESSION['success'] = 'Order placed successfully! Order ID: #' . $order_id;
        redirect('payment.php?order_id=' . $order_id);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error placing order: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <h2 class="mb-4">Checkout</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Delivery Address</h5>
                        <a href="address-form.php?redirect=<?php echo urlencode('checkout.php'); ?>" class="btn btn-sm btn-outline-primary">
                            <?php echo $address ? 'Change Address' : 'Add Address'; ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($address): ?>
                            <div class="address-display">
                                <h6><?php echo htmlspecialchars($address['full_name']); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($address['address']); ?></p>
                                <p class="mb-1"><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode']); ?></p>
                                <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-muted">No delivery address added</p>
                                <a href="address-form.php?redirect=<?php echo urlencode('checkout.php'); ?>" class="btn btn-primary">Add Address</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="uploads/products/<?php echo htmlspecialchars($item['product']['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product']['name']); ?>" 
                                     class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                    <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div class="text-end">
                                    <div><?php echo formatCurrency($item['subtotal']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span><?php echo formatCurrency($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span class="text-success">Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <?php if ($address): ?>
                            <form method="post">
                                <div class="d-grid">
                                    <button type="submit" name="place_order" class="btn btn-primary btn-lg">Place Order</button>
                                    <small class="text-muted text-center">
                                        By placing this order, you agree to our 
                                        <a href="terms.php" target="_blank">Terms and Conditions</a>
                                    </small>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="d-grid">
                                <a href="address-form.php?redirect=<?php echo urlencode('checkout.php'); ?>" class="btn btn-primary btn-lg">Add Address to Continue</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>