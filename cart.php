<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACTION: Add to Cart or Buy Now
    if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $custom_text = isset($_POST['custom_text']) ? trim($_POST['custom_text']) : null;
        $custom_file = $_FILES['custom_file'] ?? null;
        $custom_file_name = null;
        
        if ($custom_file && $custom_file['error'] == UPLOAD_ERR_OK) {
            $target_dir = ORDER_ATTACHMENT_DIR;
            $file_name = time() . '_' . uniqid() . '_' . basename($custom_file["name"]);
            $target_file = $target_dir . $file_name;
            if (!move_uploaded_file($custom_file["tmp_name"], $target_file)) {
                $_SESSION['error'] = 'Could not upload custom file.';
                redirect('product.php?id=' . $product_id);
            }
            $custom_file_name = $file_name;
        }
        
        $cart_item_key = md5($product_id . ($custom_text ?? '') . ($custom_file_name ?? ''));

        if (isset($_SESSION['cart'][$cart_item_key])) {
            $_SESSION['cart'][$cart_item_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cart_item_key] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'custom_text' => $custom_text,
                'custom_file' => $custom_file_name
            ];
        }
        
        if (isset($_POST['buy_now'])) {
            redirect('checkout.php');
        } else {
            $_SESSION['success'] = 'Product added to cart!';
            redirect('cart.php');
        }
    }
    
    // ACTION: Update Quantity
    elseif (isset($_POST['update_quantity'])) {
        $cart_item_key = $_POST['cart_item_key'];
        $quantity = (int)$_POST['quantity'];
        
        if (isset($_SESSION['cart'][$cart_item_key])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$cart_item_key]['quantity'] = $quantity;
                $_SESSION['success'] = 'Cart updated!';
            } else {
                unset($_SESSION['cart'][$cart_item_key]);
                $_SESSION['success'] = 'Item removed from cart!';
            }
        }
        redirect('cart.php');
    }
    
    // ACTION: Remove Item
    elseif (isset($_POST['remove_item'])) {
        $cart_item_key = $_POST['cart_item_key'];
        unset($_SESSION['cart'][$cart_item_key]);
        $_SESSION['success'] = 'Item removed from cart!';
        redirect('cart.php');
    }
}

// Get cart items for display
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids = array_unique(array_column($_SESSION['cart'], 'product_id'));
    
    if(!empty($product_ids)) {
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $products = [];
        foreach($products_db as $p) {
            $products[$p['id']] = $p;
        }
        
        foreach ($_SESSION['cart'] as $key => $item) {
            if (isset($products[$item['product_id']])) {
                $product = $products[$item['product_id']];
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;
                
                $cart_items[] = [
                    'key' => $key,
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'custom_text' => $item['custom_text'],
                    'custom_file' => $item['custom_file']
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <h2 class="mb-4">Shopping Cart</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <h4>Your cart is empty</h4>
                <p>Add some products to get started!</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="uploads/products/<?php echo htmlspecialchars($item['product']['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product']['name']); ?>" 
                                             class="img-fluid rounded">
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo htmlspecialchars($item['product']['name']); ?></h5>
                                        <p class="text-muted mb-1"><?php echo formatCurrency($item['product']['price']); ?> each</p>
                                        <?php if ($item['custom_text']): ?>
                                            <small class="d-block text-info"><strong>Note:</strong> <?php echo nl2br(htmlspecialchars($item['custom_text'])); ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['custom_file']): ?>
                                            <small class="d-block text-info"><strong>File:</strong> <?php echo htmlspecialchars($item['custom_file']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="cart_item_key" value="<?php echo $item['key']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['product']['stock']; ?>" 
                                                   class="form-control me-2" style="width: 80px;">
                                            <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary">Update</button>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong><?php echo formatCurrency($item['subtotal']); ?></strong>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <form method="post">
                                            <input type="hidden" name="cart_item_key" value="<?php echo $item['key']; ?>">
                                            <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">×</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal:</span>
                                <span><?php echo formatCurrency($total); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong><?php echo formatCurrency($total); ?></strong>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="checkout.php" class="btn btn-primary btn-lg">Proceed to Checkout</a>
                                <a href="products.php" class="btn btn-outline-secondary">Continue Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>