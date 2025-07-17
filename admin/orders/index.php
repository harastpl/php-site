<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $admin_price = isset($_POST['admin_price']) ? (float)$_POST['admin_price'] : 0;
    
    if ($status == 'processing' && $admin_price > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_price = ?, final_total = ? WHERE id = ?");
        $stmt->execute([$status, $admin_price, $admin_price, $order_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
    }
    
    $_SESSION['success'] = 'Order status updated successfully!';
    redirect('index.php');
}

// Get all orders
$stmt = $pdo->query("
    SELECT o.*, u.username, u.email, u.address,
           oi.custom_stl, oi.custom_notes, oi.infill_percentage, oi.layer_height, 
           oi.support_needed, oi.quantity,
           c.name as color_name, CONCAT('#', c.hex_code) as hex_code,
           m.name as material_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN colors c ON oi.color_id = c.id
    LEFT JOIN materials m ON oi.material_id = m.id
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
                                    <strong>Qty:</strong> <?php echo $order['quantity']; ?><br>
                                    <?php if ($order['material_name']): ?>
                                        <strong>Material:</strong> <?php echo htmlspecialchars($order['material_name']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($order['color_name']): ?>
                                        <strong>Color:</strong> 
                                        <span style="display: inline-block; width: 12px; height: 12px; background-color: <?php echo $order['hex_code']; ?>; border-radius: 50%; border: 1px solid #ccc;"></span>
                                        <?php echo htmlspecialchars($order['color_name']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($order['infill_percentage']): ?>
                                        <strong>Infill:</strong> <?php echo $order['infill_percentage']; ?>%<br>
                                    <?php endif; ?>
                                    <?php if ($order['layer_height']): ?>
                                        <strong>Layer:</strong> <?php echo $order['layer_height']; ?>mm<br>
                                    <?php endif; ?>
                                    <strong>Support:</strong> <?php echo $order['support_needed'] ? 'Yes' : 'No'; ?>
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
                                    <?php if ($order['custom_stl']): ?>
                                        <a href="../uploads/stl_files/<?php echo $order['custom_stl']; ?>" 
                                           class="btn btn-sm btn-outline-primary" download>Download</a><br>
                                    <?php endif; ?>
                                    
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm mb-2" onchange="togglePriceInput(this)">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <div class="price-input" style="display: none;">
                                            <input type="number" step="0.01" name="admin_price" class="form-control form-control-sm mb-2" 
                                                   placeholder="Enter final price" value="<?php echo $order['admin_price'] ?? ''; ?>">
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
            if (select.value === 'processing') {
                priceInput.style.display = 'block';
                priceInput.querySelector('input').required = true;
            } else {
                priceInput.style.display = 'none';
                priceInput.querySelector('input').required = false;
            }
        }
        
        // Initialize price inputs on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name="status"]').forEach(function(select) {
                togglePriceInput(select);
            });
        });
    </script>
</body>
</html>