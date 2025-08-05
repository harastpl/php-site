<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/email_functions.php';

requireAdmin();

// Handle status and delivery update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $admin_price = isset($_POST['admin_price']) ? (float)$_POST['admin_price'] : 0;
    $delivery_partner = trim($_POST['delivery_partner']);
    $tracking_id = trim($_POST['tracking_id']);
    
    // Get order and user details for email notifications
    $stmt_order_details = $pdo->prepare("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt_order_details->execute([$order_id]);
    $order_details = $stmt_order_details->fetch();
    
    $send_delivery_email = false;
    $send_price_email = false;
    
    // Check if delivery info is being updated
    if (!empty($delivery_partner) && !empty($tracking_id)) {
        $stmt_check_delivery = $pdo->prepare("SELECT delivery_partner, tracking_id FROM orders WHERE id = ?");
        $stmt_check_delivery->execute([$order_id]);
        $current_delivery = $stmt_check_delivery->fetch();
        
        if ($current_delivery['delivery_partner'] != $delivery_partner || $current_delivery['tracking_id'] != $tracking_id) {
            $send_delivery_email = true;
        }
    }
    
    // Update delivery details
    $stmt_delivery = $pdo->prepare("UPDATE orders SET delivery_partner = ?, tracking_id = ? WHERE id = ?");
    $stmt_delivery->execute([$delivery_partner, $tracking_id, $order_id]);

    // Only update price for custom orders when status is 'processing'
    $stmt_check_custom = $pdo->prepare("SELECT custom_stl FROM order_items WHERE order_id = ?");
    $stmt_check_custom->execute([$order_id]);
    $is_custom_order = $stmt_check_custom->fetchColumn();

    if ($is_custom_order && $status == 'processing' && $admin_price > 0) {
        // Check if price is being updated
        if ($order_details['admin_price'] != $admin_price) {
            $send_price_email = true;
        }
        
        // Calculate discount if applicable
        $stmt_order = $pdo->prepare("SELECT oi.quantity FROM order_items oi WHERE oi.order_id = ?");
        $stmt_order->execute([$order_id]);
        $order_item = $stmt_order->fetch();
        
        $discount = calculateBulkDiscount($order_item['quantity'] ?? 1, $admin_price);
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_price = ?, total = ?, discount_amount = ?, final_total = ? WHERE id = ?");
        $stmt->execute([$status, $admin_price, $admin_price, $discount['amount'], $discount['final_total'], $order_id]);
        
        // Update order item price
        $stmt = $pdo->prepare("UPDATE order_items SET price = ? WHERE order_id = ?");
        $stmt->execute([$discount['final_total'], $order_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
    }
    
    // Send email notifications
    if ($order_details && $order_details['email']) {
        if ($send_price_email && $admin_price > 0) {
            sendPriceUpdateEmail($order_details['email'], $order_id, $admin_price);
        }
        
        if ($send_delivery_email) {
            sendDeliveryUpdateEmail($order_details['email'], $order_id, $delivery_partner, $tracking_id);
        }
    }
    
    $_SESSION['success'] = 'Order updated successfully!';
    redirect('index.php');
}

// Get all orders
$stmt = $pdo->query("
    SELECT o.*, u.username, u.email, u.address,
           oi.product_id, oi.custom_stl, oi.custom_notes, oi.infill_percentage, oi.layer_height, 
           oi.support_needed, oi.quantity, oi.custom_text, oi.custom_file_upload, oi.print_type,
           c.name as color_name, CONCAT('#', c.hex_code) as hex_code,
           m.name as material_name,
           p.name as product_name, p.stl_file as product_stl_file
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN colors c ON oi.color_id = c.id
    LEFT JOIN materials m ON oi.material_id = m.id
    LEFT JOIN products p ON oi.product_id = p.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin-sidebar2.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Orders</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Details</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['username'] ?? 'Guest'); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['email'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php if ($order['product_name']): ?>
                                        <strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?><br>
                                        <?php if ($order['custom_text']): ?>
                                            <small class="text-info"><strong>Note:</strong> <?php echo htmlspecialchars($order['custom_text']); ?></small><br>
                                        <?php endif; ?>
                                        <?php if ($order['custom_file_upload']): ?>
                                            <small class="text-info"><strong>File:</strong> <a href="../../uploads/order_attachments/<?php echo htmlspecialchars($order['custom_file_upload']); ?>" download>Download</a></small><br>
                                        <?php endif; ?>
                                    <?php elseif ($order['custom_stl']): ?>
                                        <strong>Custom Order</strong><br>
                                        <strong>Print Type:</strong> <?php echo htmlspecialchars($order['print_type'] ?? 'FDM'); ?><br>
                                    <?php endif; ?>

                                    <strong>Qty:</strong> <?php echo $order['quantity']; ?><br>
                                    <?php if ($order['material_name']): ?>
                                        <strong>Material:</strong> <?php echo htmlspecialchars($order['material_name']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($order['color_name']): ?>
                                        <strong>Color:</strong> 
                                        <span style="display: inline-block; width: 12px; height: 12px; background-color: <?php echo $order['hex_code']; ?>; border-radius: 50%; border: 1px solid #ccc;"></span>
                                        <?php echo htmlspecialchars($order['color_name']); ?><br>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order['discount_amount'] > 0): ?>
                                        <span class="text-decoration-line-through"><?php echo formatCurrency($order['total']); ?></span><br>
                                        <strong><?php echo formatCurrency($order['final_total']); ?></strong><br>
                                        <small class="text-success">Discount: <?php echo formatCurrency($order['discount_amount']); ?></small>
                                    <?php else: ?>
                                        <strong><?php echo formatCurrency($order['final_total']); ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] == 'pending' ? 'warning' : 
                                             ($order['status'] == 'processing' ? 'info' : 
                                             ($order['status'] == 'completed' ? 'success' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['payment_status'] == 'pending' ? 'secondary' : 
                                             ($order['payment_status'] == 'paid' ? 'success' : 
                                             ($order['payment_status'] == 'failed' ? 'danger' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php if (!empty($order['address'])): ?>
                                        <?php 
                                        $address = json_decode($order['address'], true);
                                        if ($address): 
                                        ?>
                                            <div class="mb-2">
                                                <small><strong>Address:</strong><br>
                                                <?php echo htmlspecialchars($address['full_name']); ?><br>
                                                <?php echo htmlspecialchars($address['address']); ?><br>
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode']); ?><br>
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($address['phone']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['custom_notes']): ?>
                                        <div class="mb-2">
                                            <small><strong>Additional Info:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($order['custom_notes'])); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['custom_stl']): ?>
                                        <?php 
                                        $files = explode(',', $order['custom_stl']);
                                        foreach ($files as $file): 
                                        ?>
                                            <a href="../../uploads/stl_files/<?php echo htmlspecialchars(trim($file)); ?>" 
                                               class="btn btn-sm btn-outline-primary d-block mb-1" download>Download <?php echo htmlspecialchars(trim($file)); ?></a>
                                        <?php endforeach; ?>
                                    <?php elseif ($order['product_stl_file']): ?>
                                        <a href="../../uploads/stl_files/<?php echo htmlspecialchars($order['product_stl_file']); ?>" 
                                           class="btn btn-sm btn-outline-success d-block mb-2" download>Download Product STL</a>
                                    <?php endif; ?>
                                    
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm mb-2" 
                                                data-order-type="<?php echo $order['custom_stl'] ? 'custom' : 'product'; ?>"
                                                onchange="togglePriceInput(this)">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <div class="price-input" style="display: none;">
                                            <input type="number" step="0.01" name="admin_price" class="form-control form-control-sm mb-2" 
                                                   placeholder="Enter final price" value="<?php echo $order['admin_price'] ?? ''; ?>">
                                            <small class="text-muted">This will be the base price before any bulk discounts</small>
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="delivery_partner" class="form-control form-control-sm" placeholder="Delivery Partner" value="<?php echo htmlspecialchars($order['delivery_partner'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="tracking_id" class="form-control form-control-sm" placeholder="Tracking ID" value="<?php echo htmlspecialchars($order['tracking_id'] ?? ''); ?>">
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePriceInput(select) {
            const priceInput = select.parentElement.querySelector('.price-input');
            const orderType = select.getAttribute('data-order-type');
            
            if (select.value === 'processing' && orderType === 'custom') {
                priceInput.style.display = 'block';
                priceInput.querySelector('input').required = true;
            } else {
                priceInput.style.display = 'none';
                priceInput.querySelector('input').required = false;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name="status"]').forEach(function(select) {
                togglePriceInput(select);
            });
        });
    </script>
</body>
</html>