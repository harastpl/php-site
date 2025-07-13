<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($id);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header("Location: products.php");
    exit();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to add items to cart.';
        header("Location: login.php");
        exit();
    }
    
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && $quantity <= $product['stock']) {
        // Add to cart logic here
        $_SESSION['success'] = 'Product added to cart successfully!';
    } else {
        $_SESSION['error'] = 'Invalid quantity or insufficient stock.';
    }
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="mb-3">
                    <span class="price"><?php echo formatCurrency($product['price']); ?></span>
                </div>
                
                <div class="mb-3">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?> available)</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="post" action="cart.php" class="mb-3">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       min="1" max="<?php echo $product['stock']; ?>" value="1" required>
                            </div>
                            <div class="col-md-8">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary" onclick="addToCart(event)">Add to Cart</button>
                                    <a href="checkout.php?product_id=<?php echo $product['id']; ?>&quantity=1" 
                                       class="btn btn-success" onclick="updateBuyNowLink(this)">Buy Now</a>
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
        function updateBuyNowLink(element) {
            const quantity = document.getElementById('quantity').value;
            const productId = <?php echo $product['id']; ?>;
            element.href = `checkout.php?product_id=${productId}&quantity=${quantity}`;
        }
        
        document.getElementById('quantity').addEventListener('change', function() {
            const buyNowBtn = document.querySelector('a[href*="checkout.php"]');
            updateBuyNowLink(buyNowBtn);
        });
    </script>
</body>
</html>