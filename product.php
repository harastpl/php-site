<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($id);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-6">
                <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="img-fluid rounded">
            </div>
            <div class="col-md-6">
                <h1 class="glow"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="mb-3">
                    <span class="h3 text-primary"><?php echo formatCurrency($product['price']); ?></span>
                </div>
                
                <div class="mb-3">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?> available)</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-info">
                    <h6>Custom 3D Printing Available!</h6>
                    <p>Want this design customized or have your own design? Use our <a href="custom-order.php">Custom Order</a> system for personalized 3D prints.</p>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="custom-order.php" class="btn btn-primary btn-lg">Order Custom Print</a>
                    <a href="index.php" class="btn btn-secondary">Back to Products</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>