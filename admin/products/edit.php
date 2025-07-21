<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($id);

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $stock = (int)$_POST['stock'];
    $low_stock_threshold = (int)$_POST['low_stock_threshold'];
    $image = $_FILES['image'];
    
    $errors = [];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = 'Valid price is required';
    }
    
    if ($stock < 0) {
        $errors[] = 'Stock cannot be negative';
    }
    
    if (empty($errors)) {
        $imageName = $product['image']; // Keep existing image by default
        
        // Handle image upload if new image provided
        if ($image['error'] != UPLOAD_ERR_NO_FILE) {
            $target_dir = PRODUCT_IMAGE_DIR;
            $file_name = time() . '_' . basename($image["name"]);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Validate image
            $check = getimagesize($image["tmp_name"]);
            if ($check === false) {
                $errors[] = 'File is not an image.';
            }
            
            if ($image["size"] > 5000000) {
                $errors[] = 'Sorry, your file is too large. Max 5MB allowed.';
            }
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($imageFileType, $allowed_types)) {
                $errors[] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
            }
            
            if (empty($errors)) {
                if (move_uploaded_file($image["tmp_name"], $target_file)) {
                    // Delete old image
                    $oldImagePath = $target_dir . $product['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                    $imageName = $file_name;
                } else {
                    $errors[] = 'Sorry, there was an error uploading your file.';
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, low_stock_threshold = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock, $low_stock_threshold, $imageName, $id]);
                
                $_SESSION['success'] = 'Product updated successfully!';
                redirect('index.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
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
    <title>Edit Product | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin-sidebar2.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Product</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                               value="<?php echo $product['price']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" id="stock" name="stock" 
                                               value="<?php echo $product['stock']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="low_stock_threshold" class="form-label">Low Stock Alert</label>
                                        <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                               value="<?php echo $product['low_stock_threshold']; ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <div class="mb-2">
                                    <img src="../uploads/products/<?php echo $product['image']; ?>" 
                                         alt="Current product image" class="img-fluid" style="max-height: 200px;">
                                </div>
                                <label for="image" class="form-label">New Image (optional)</label>
                                <input type="file" class="form-control" id="image" name="image">
                                <div class="form-text">Leave empty to keep current image. Max 5MB.</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>