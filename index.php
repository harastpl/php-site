<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$products = getFeaturedProducts(6); // Get 6 featured products
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 3D Printing Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5">
        <section class="hero mb-5">
            <div class="row">
                <div class="col-md-6">
                    <h1>Custom 3D Printing Services</h1>
                    <p class="lead">High-quality 3D prints from your designs or ours.</p>
                    <a href="custom-order.php" class="btn btn-primary btn-lg">Upload Your STL File</a>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/3d-printer.jpg" alt="3D Printer" class="img-fluid rounded">
                </div>
            </div>
        </section>

        <section class="featured-products">
            <h2 class="mb-4">Featured Products</h2>
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'">
                    <div class="card h-100 product-card-clickable">
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>