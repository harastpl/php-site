<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Get user's orders
$stmt = $pdo->prepare("
    SELECT o.*,
           oi.product_id, oi.custom_stl, oi.custom_notes, oi.infill_percentage, oi.layer_height,
           oi.support_needed, oi.quantity,
           c.name as color_name, c.hex_code,
           m.name as material_name,
           p.name as product_name
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN colors c ON oi.color_id = c.id
    LEFT JOIN materials m ON oi.material_id = m.id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user address separately to avoid issues with multiple order items
$stmt_user = $pdo->prepare("SELECT address FROM users WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user = $stmt_user->fetch();

$address = null;
if (!empty($user['address'])) {
    $address = json_decode($user['address'], true);
}

// Flash message handling
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #e0e0ff;
            min-height: 100vh;
        }
        .card, .order-card {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card-header {
             background-color: rgba(30, 30, 50, 0.9);
             border-bottom: 1px solid rgba(167, 139, 250, 0.2);
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 15px;
        }
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid #fff;
            vertical-align: middle;
            margin-right: 5px;
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
        
        <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash_message['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash_message['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Delivery Address</h5>
                <a href="address-form.php?redirect=orders.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit"></i> <?php echo $address ? 'Change' : 'Add'; ?> Address
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
                        <p class="text-muted mb-0">No delivery address added. Please add one before checkout.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <h4>No orders found</h4>
                <p>You haven't placed any orders yet.</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
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
                                echo strtolower($order['payment_status']) == 'pending' ? 'warning' : 
                                     (strtolower($order['payment_status']) == 'paid' ? 'success' : 
                                     (strtolower($order['payment_status']) == 'failed' ? 'danger' : 'secondary')); 
                            ?>">
                                Payment: <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <?php if ($order['product_id']): ?>
                                    <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                                <?php else: ?>
                                    <p><strong>Print Type:</strong> <?php echo htmlspecialchars($order['print_type'] ?? 'FDM'); ?></p>
                                    <p><strong>Material:</strong> <?php echo htmlspecialchars($order['material_name']); ?></p>
                                    <p><strong>Color:</strong> <span class="color-preview" style="background-color:<?php echo $order['hex_code']; ?>"></span> <?php echo htmlspecialchars($order['color_name']); ?></p>
                                    <p><strong>Infill:</strong> <?php echo $order['infill_percentage']; ?>%</p>
                                    <p><strong>Layer Height:</strong> <?php echo $order['layer_height']; ?>mm</p>
                                    <p><strong>Supports:</strong> <?php echo $order['support_needed'] ? 'Yes' : 'No'; ?></p>
                                <?php endif; ?>
                                <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <p class="mb-1"><strong>Total:</strong>
                                    <span>
                                        <?php if ($order['final_total'] > 0): ?>
                                            <?php echo formatCurrency($order['final_total']); ?>
                                        <?php else: ?>
                                            <em class="text-muted">To be updated soon</em>
                                        <?php endif; ?>
                                    </span>
                                </p>
                                <?php if (!empty($order['delivery_partner']) && !empty($order['tracking_id'])): ?>
                                    <p class="mb-1"><strong>Delivery Partner:</strong> <?php echo htmlspecialchars($order['delivery_partner']); ?></p>
                                    <p class="mb-0"><strong>Tracking ID:</strong> <?php echo htmlspecialchars($order['tracking_id']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($order['custom_stl']): ?>
                            <div class="mt-3">
                                <p><strong>Design Files:</strong></p>
                                <?php 
                                $files = explode(',', $order['custom_stl']);
                                foreach ($files as $file): 
                                ?>
                                    <a href="uploads/stl_files/<?php echo trim($file); ?>" 
                                       class="btn btn-sm btn-outline-primary me-2 mb-2" download>
                                        <i class="fas fa-download"></i> <?php echo htmlspecialchars(trim($file)); ?>
                                    </a>
                                <?php endforeach; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['custom_notes']): ?>
                            <div class="mt-3">
                                <p><strong>Additional Information:</strong></p>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['custom_notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 text-end">
                            <?php if (strtolower($order['payment_status']) == 'pending' && $order['final_total'] > 0): ?>
                                <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </a>
                            <?php elseif ($order['final_total'] == 0): ?>
                                <span class="badge bg-info">Waiting for review</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>