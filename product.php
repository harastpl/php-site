<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($id);
$productImages = getProductImages($id);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header("Location: products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-6">
                <div class="product-image-gallery">
                    <?php if (!empty($productImages)): ?>
                        <img id="mainImage" src="uploads/products/<?php echo htmlspecialchars($productImages[0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image-main shadow">
                        
                        <?php if (count($productImages) > 1): ?>
                            <div class="product-image-thumbnails">
                                <?php foreach ($productImages as $index => $image): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeMainImage(this, '<?php echo htmlspecialchars($image['image_path']); ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image-main shadow">
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="mb-3">
                    <span class="price"><?php echo formatCurrency($product['price']); ?></span>
                </div>
                
                <div class="mb-3">
                    <?php if ($product['stock'] <= 0): ?>
                        <span class="badge bg-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="post" action="cart.php" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                        <?php if ($product['enable_text_field']): ?>
                            <div class="mb-3">
                                <label for="custom_text" class="form-label"><?php echo htmlspecialchars($product['text_field_label']); ?><?php echo $product['text_field_required'] ? ' *' : ''; ?></label>
                                <textarea class="form-control" id="custom_text" name="custom_text" <?php echo $product['text_field_required'] ? 'required' : ''; ?>></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if ($product['enable_file_upload']): ?>
                            <div class="mb-3">
                                <label for="custom_file" class="form-label"><?php echo htmlspecialchars($product['file_upload_label']); ?><?php echo $product['file_upload_required'] ? ' *' : ''; ?></label>
                                <input type="file" class="form-control" id="custom_file" name="custom_file" <?php echo $product['file_upload_required'] ? 'required' : ''; ?>>
                            </div>
                        <?php endif; ?>
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       min="1" max="<?php echo $product['stock']; ?>" value="1" required>
                            </div>
                            <div class="col-md-8">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                                    <button type="submit" name="buy_now" class="btn btn-gradient-success buy-now-btn">Buy Now</button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6>Custom 3D Printing Available!</h6>
                    <p>Want this design customized or have your own design? Use our <a href="custom-order.php">Custom Order</a> system for personalized 3D prints.</p>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="custom-order.php" class="btn btn-secondary">Order Custom Print</a>
                    <a href="products.php" class="btn btn-outline-primary">Back to Products</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function changeMainImage(thumbnail, imagePath) {
            document.getElementById('mainImage').src = 'uploads/products/' + imagePath;
            
            document.querySelectorAll('.product-image-thumb').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        // Auto-carousel for the main product image
        const productImages = <?php echo json_encode(array_column($productImages, 'image_path')); ?>;
        const mainImage = document.getElementById('mainImage');
        let currentImageIndex = 0;

        if (mainImage && productImages.length > 1) {
            setInterval(() => {
                mainImage.style.opacity = 0; // Start fade out
                setTimeout(() => {
                    currentImageIndex = (currentImageIndex + 1) % productImages.length;
                    mainImage.src = 'uploads/products/' + productImages[currentImageIndex];
                    mainImage.style.opacity = 1; // Start fade in
                }, 100); // Wait for fade out to complete
            }, 2500); // Set interval to 1 second
        }
    </script>
</body>
</html>