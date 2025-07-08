<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get product image to delete
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Delete image file
        $imagePath = PRODUCT_IMAGE_DIR . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = 'Product deleted successfully!';
    }
    
    redirect('index.php');
}

// Get all products
$products = getProducts();
$lowStockProducts = getLowStockProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin-sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Products</h1>
                    <a href="add.php" class="btn btn-primary">Add New Product</a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($lowStockProducts)): ?>
                    <div class="alert alert-warning">
                        <h5>Low Stock Alert!</h5>
                        <p>The following products are running low on stock:</p>
                        <ul>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <li><?php echo htmlspecialchars($product['name']); ?> - Only <?php echo $product['stock']; ?> left</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="../../uploads/products/<?php echo $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['stock'] <= $product['low_stock_threshold'] ? 'warning' : 'success'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['stock'] > 0): ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="index.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
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
</body>
</html>