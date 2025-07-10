<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Get stats for dashboard
$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$totalProducts = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
$totalOrders = $stmt->fetch()['total_orders'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetch()['pending_orders'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin-sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <p class="card-text display-4"><?php echo $totalProducts; ?></p>
                                <a href="products/" class="text-white">View Products</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <p class="card-text display-4"><?php echo $totalOrders; ?></p>
                                <a href="orders/" class="text-white">View Orders</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Pending Orders</h5>
                                <p class="card-text display-4"><?php echo $pendingOrders; ?></p>
                                <a href="orders/" class="text-white">View Orders</a>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="mt-5">Recent Custom Orders</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>STL File</th>
                                <th>Notes</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT o.id, o.created_at, o.status, u.username, oi.custom_stl, oi.custom_notes 
                                FROM orders o
                                LEFT JOIN users u ON o.user_id = u.id
                                JOIN order_items oi ON o.id = oi.order_id
                                WHERE oi.custom_stl IS NOT NULL
                                ORDER BY o.created_at DESC
                                LIMIT 5
                            ");
                            while ($order = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['username'] ?? 'Guest'; ?></td>
                                <td>
                                    <a href="../uploads/stl_files/<?php echo $order['custom_stl']; ?>" download>
                                        Download STL
                                    </a>
                                </td>
                                <td><?php echo substr($order['custom_notes'], 0, 50) . '...'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
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
                                    <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>