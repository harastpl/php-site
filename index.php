<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$products = getFeaturedProducts(); // Get all featured products
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - 3D Printing Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 ">
        <section class="hero mb-5 rounded-4" style="padding: 16px 12px; margin-bottom: 5px; min-height: 240px;"">
            <div class="row">
                <div class="col-md-6">
                    <h1 style="size:22px">Custom 3D Printing Services</h1>
                    <p class="lead" style="size:14px">High-quality 3D prints from your designs or ours.</p>
                    <a href="custom-order.php" class="btn btn-primary btn-lg">Upload Your STL File</a>
                </div>
                <div class="col-md-6">
                    <img src="assets/images/3d-printer.jpg" alt="3D Printer" class="img-fluid rounded-5" style="padding: 15px">
                </div>
            </div>
        </section>

        <section class="featured-products">
            <h2 class="mb-4" style="padding: 5px">Featured Products</h2>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <?php 
                        $galleryImages = !empty($product['image_gallery']) ? explode(',', $product['image_gallery']) : [$product['image']];
                        $primaryImage = $galleryImages[0];
                    ?>
                    <div class="col-md-4 mb-4" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'">
                        <div class="card h-100 product-card-clickable" 
                             data-gallery='<?php echo json_encode($galleryImages); ?>' 
                             data-primary-image="<?php echo htmlspecialchars($primaryImage); ?>">
                            
                            <img src="uploads/products/<?php echo htmlspecialchars($primaryImage); ?>" class="card-img-top product-card-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <!-- <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p> -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-only">
        <div class="mobile-bottom-nav">
            <a href="index.php" class="active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="products.php" >
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="orders.php">
                <i class="fas fa-user"></i>
                <span>Account</span>
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
            </a>
        </div>
    </div>


    <?php include 'includes/footer.php'; ?>

</body>
</html>