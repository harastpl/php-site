<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get all products
$products = getProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>All Products</h2>
            <a href="custom-order.php" class="btn btn-primary">Custom Order</a>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <h4>No products available</h4>
                <p>Check back later for new products or place a custom order.</p>
                <a href="custom-order.php" class="btn btn-primary">Place Custom Order</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="card h-100">
                    <?php if ($product['is_featured']): ?>
                        <div class="featured-badge">Featured</div>
                    <?php endif; ?>
                    <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                        
                        <div class="mb-3">
                            <span class="price"><?php echo formatCurrency($product['price']); ?></span>
                            <?php if ($product['stock'] > 0): ?>
                                <span class="badge bg-success ms-2">In Stock (<?php echo $product['stock']; ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>