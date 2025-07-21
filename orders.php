<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Get user's orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           oi.custom_stl, oi.custom_notes, oi.infill_percentage, oi.layer_height, 
           oi.support_needed, oi.quantity,
           c.name as color_name, c.hex_code,
           m.name as material_name
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN colors c ON oi.color_id = c.id
    LEFT JOIN materials m ON oi.material_id = m.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | <?php echo SITE_NAME; ?></title>
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
        .order-card {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
        }
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid #fff;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="glow">My Orders</h2>
            <a href="custom-order.php" class="btn btn-primary">New Custom Order</a>
        </div>
        
        <!-- Address Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Delivery Address</h5>
                <?php if (empty($user['address'])): ?>
                    <a href="address-form.php?redirect=orders.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Address
                    </a>
                <?php else: ?>
                    <a href="address-form.php?redirect=orders.php" class="btn btn-outline-primary btn-sm">
                        Change Address
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
            <?php 
            $stmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            $address = null;
            if (!empty($user['address'])) {
                $address = json_decode($user['address'], true);
            }
            ?>
            <?php if ($address): ?>
                <div class="address-display">
                    <h6><?php echo htmlspecialchars($address['full_name']); ?></h6>
                    <p class="mb-1"><?php echo htmlspecialchars($address['address']); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode']); ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?></p>
                </div>
            <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No delivery address added</p>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <h4>No orders found</h4>
                <p>You haven't placed any orders yet.</p>
                <a href="custom-order.php" class="btn btn-primary">Place Your First Order</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #<?php echo $order['id']; ?></h5>
                        <div>
                            <span class="badge status-badge bg-<?php 
                                echo $order['status'] == 'pending' ? 'warning' : 
                                     ($order['status'] == 'processing' ? 'info' : 
                                     ($order['status'] == 'completed' ? 'success' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <span class="badge status-badge bg-<?php 
                                echo $order['payment_status'] == 'pending' ? 'secondary' : 
                                     ($order['payment_status'] == 'paid' ? 'success' : 
                                     ($order['payment_status'] == 'failed' ? 'danger' : 'warning')); 
                            ?>">
                                Payment: <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                                <?php if ($order['material_name']): ?>
                                    <p><strong>Material:</strong> <?php echo htmlspecialchars($order['material_name']); ?></p>
                                <?php endif; ?>
                                <?php if ($order['color_name']): ?>
                                    <p><strong>Color:</strong> 
                                        <span class="color-preview" style="background-color: <?php echo $order['hex_code']; ?>"></span>
                                        <?php echo htmlspecialchars($order['color_name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($order['infill_percentage']): ?>
                                    <p><strong>Infill:</strong> <?php echo $order['infill_percentage']; ?>%</p>
                                <?php endif; ?>
                                <?php if ($order['layer_height']): ?>
                                    <p><strong>Layer Height:</strong> <?php echo $order['layer_height']; ?>mm</p>
                                <?php endif; ?>
                                <p><strong>Support:</strong> <?php echo $order['support_needed'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Total:</strong> 
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <span class="text-muted">Pending admin review</span>
                                    <?php elseif ($order['discount_amount'] > 0): ?>
                                        <span class="text-decoration-line-through"><?php echo formatCurrency($order['total']); ?></span>
                                        <span class="text-success"><?php echo formatCurrency($order['final_total']); ?></span>
                                        <small class="text-muted">(<?php echo formatCurrency($order['discount_amount']); ?> discount)</small>
                                    <?php else: ?>
                                        <?php echo formatCurrency($order['final_total']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($order['custom_stl']): ?>
                            <div class="mt-3">
                                <p><strong>Design File:</strong> 
                                    <a href="uploads/stl_files/<?php echo $order['custom_stl']; ?>" 
                                       class="btn btn-sm btn-outline-primary" download>
                                        Download File
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['custom_notes']): ?>
                            <div class="mt-3">
                                <p><strong>Notes:</strong></p>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['custom_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['payment_status'] == 'pending' && $order['status'] == 'processing' && $order['admin_price'] > 0): ?>
                            <?php if ($order['custom_stl']): ?>
                            <div class="mt-3">
                                <a href="payment.php?order_id=<?php echo $order['id']; ?>" 
                                   class="btn btn-success">Pay Now</a>
                            </div>
                            <?php endif; ?>
                        <?php elseif ($order['status'] == 'pending'): ?>
                            <div class="mt-3">
                                <?php if ($order['custom_stl']): ?>
                                    <span class="badge bg-info">Waiting for admin pricing</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    
    <script>
        function showAddressForm() {
            document.getElementById('address-form').style.display = 'block';
        }
        
        function hideAddressForm() {
            document.getElementById('address-form').style.display = 'none';
        }
    </script>
</body>
</html>