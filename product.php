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

include 'includes/header.php';
?>
<style>
@media (max-width: 768px) {
  nav.navbar.mobile-only { display: none !important; }
}
</style>

<!-- Desktop Product View -->
<div class="desktop-only">
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
            <h1 style="font-size: 24px; padding:2px;"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="lead" style="font-size: 14px;">&nbsp;<?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
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
                            <label for="custom_text" class="form-label" ><?php echo htmlspecialchars($product['text_field_label']); ?><?php echo $product['text_field_required'] ? ' *' : ''; ?></label>
                            <textarea class="form-control" id="custom_text" name="custom_text" <?php echo $product['text_field_required'] ? 'required' : ''; ?>></textarea>
                        </div>
                    <?php endif; ?>

                    <?php if ($product['enable_file_upload']): ?>
                        <div class="mb-3">
                            <label for="custom_file" class="form-label"><?php echo htmlspecialchars($product['file_upload_label']); ?><?php echo $product['file_upload_required'] ? ' *' : ''; ?></label>
                            <input type="file" class="form-control" id="custom_file" name="custom_file" <?php echo $product['file_upload_required'] ? 'required' : ''; ?>>
                        </div>
                    <?php endif; ?>
                    <div class="row align-items-end" style="padding:4px;">
                        <div class="col-md-4" >
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
</div>

<!-- Mobile Search Header -->
<div class="mobile-only">
    <div class="mobile-search-header" style="background-color: #008BFF; padding: 1px;">
        <div class="container">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm text-white border-white" style="background: transparent;" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="flex-grow-1">
                    <input type="text" class="form-control form-control-sm" placeholder="Search products..." onclick="window.location.href='products.php'">
                </div>
                <button class="btn btn-sm text-white border-white" style="background: transparent;" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Product View -->
<div class="mobile-only">
    <div class="container mt-3 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="product-image-gallery mb-3">
                    <?php if (!empty($productImages)): ?>
                        <img id="mobileMainImage" src="uploads/products/<?php echo htmlspecialchars($productImages[0]['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="img-fluid rounded shadow">
                        
                        <?php if (count($productImages) > 1): ?>
                            <div class="product-image-thumbnails mt-2">
                                <?php foreach ($productImages as $index => $image): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="changeMobileMainImage(this, '<?php echo htmlspecialchars($image['image_path']); ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="img-fluid rounded shadow">
                    <?php endif; ?>
                </div>
                
                <h1 class="fs-4 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="mb-3">
                    <span class="price fs-5"><?php echo formatCurrency($product['price']); ?></span>
                    <?php if ($product['stock'] <= 0): ?>
                        <span class="badge bg-danger ms-2">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="post" action="cart.php" enctype="multipart/form-data" class="mb-3">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                        <?php if ($product['enable_text_field']): ?>
                            <div class="mb-3">
                                <label for="mobile_custom_text" class="form-label"><?php echo htmlspecialchars($product['text_field_label']); ?><?php echo $product['text_field_required'] ? ' *' : ''; ?></label>
                                <textarea class="form-control form-control-sm" id="mobile_custom_text" name="custom_text" <?php echo $product['text_field_required'] ? 'required' : ''; ?>></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if ($product['enable_file_upload']): ?>
                            <div class="mb-3">
                                <label for="mobile_custom_file" class="form-label"><?php echo htmlspecialchars($product['file_upload_label']); ?><?php echo $product['file_upload_required'] ? ' *' : ''; ?></label>
                                <input type="file" class="form-control form-control-sm" id="mobile_custom_file" name="custom_file" <?php echo $product['file_upload_required'] ? 'required' : ''; ?>>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row align-items-end mb-3">
                            <div class="col-6">
                                <label for="mobile_quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control form-control-sm" id="mobile_quantity" name="quantity" 
                                       min="1" max="<?php echo $product['stock']; ?>" value="1" required>
                            </div>
                            <div class="col-6">
                                <div class="d-grid gap-1">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">Add to Cart</button>
                                    <button type="submit" name="buy_now" class="btn btn-success btn-sm">Buy Now</button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6 class="small">Custom 3D Printing Available!</h6>
                    <p class="small mb-0">Want this design customized or have your own design? Use our <a href="custom-order.php">Custom Order</a> system for personalized 3D prints.</p>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="custom-order.php" class="btn btn-secondary btn-sm">Order Custom Print</a>
                    <a href="products.php" class="btn btn-outline-primary btn-sm">Back to Products</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation -->
<div class="mobile-only">
    <div class="mobile-bottom-nav">
        <a href="index.php">
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

<script>
    function changeMainImage(thumbnail, imagePath) {
        const main = document.getElementById('mainImage');
        if (!main) return;
        main.src = 'uploads/products/' + imagePath;
        document.querySelectorAll('.product-image-thumb').forEach(thumb => thumb.classList.remove('active'));
        thumbnail.classList.add('active');
    }
    
    function changeMobileMainImage(thumbnail, imagePath) {
        const main = document.getElementById('mobileMainImage');
        if (!main) return;
        main.src = 'uploads/products/' + imagePath;
        document.querySelectorAll('.product-image-thumb').forEach(thumb => thumb.classList.remove('active'));
        thumbnail.classList.add('active');
    }
    
    // Auto-carousel for the main product image (desktop + mobile)
    const productImagesArr = <?php echo json_encode(!empty($productImages) ? array_column($productImages, 'image_path') : []); ?>;
    const mainImage = document.getElementById('mainImage');
    const mobileMainImage = document.getElementById('mobileMainImage');
    let currentImageIndex = 0;

    if ((mainImage || mobileMainImage) && productImagesArr.length > 1) {
        setInterval(() => {
            if (mainImage) mainImage.style.opacity = 0;
            if (mobileMainImage) mobileMainImage.style.opacity = 0;
            setTimeout(() => {
                currentImageIndex = (currentImageIndex + 1) % productImagesArr.length;
                if (mainImage) {
                    mainImage.src = 'uploads/products/' + productImagesArr[currentImageIndex];
                    mainImage.style.opacity = 1;
                }
                if (mobileMainImage) {
                    mobileMainImage.src = 'uploads/products/' + productImagesArr[currentImageIndex];
                    mobileMainImage.style.opacity = 1;
                }
            }, 100);
        }, 2500);
    }
</script>
