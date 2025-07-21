<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

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
    
    if ($image['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Product image is required';
    }
    
    if (empty($errors)) {
        // Handle image upload
        $target_dir = PRODUCT_IMAGE_DIR;
        $file_name = time() . '_' . basename($image["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is a actual image
        $check = getimagesize($image["tmp_name"]);
        if ($check === false) {
            $errors[] = 'File is not an image.';
        }
        
        // Check file size (5MB max)
        if ($image["size"] > 5000000) {
            $errors[] = 'Sorry, your file is too large. Max 5MB allowed.';
        }
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
        }
        
        if (empty($errors)) {
            if (move_uploaded_file($image["tmp_name"], $target_file)) {
                // Insert product into database
                try {
                    $is_featured = (int)$_POST['is_featured'];
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, low_stock_threshold, image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $stock, $low_stock_threshold, $file_name, $is_featured]);
                    
                    $_SESSION['success'] = 'Product added successfully!';
                    redirect('index.php');
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Sorry, there was an error uploading your file.';
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
    <title>Add Product | <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Add New Product</h1>
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
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Alert Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="10" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="is_featured" class="form-label">Featured Product</label>
                                <select class="form-select" id="is_featured" name="is_featured">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                                <div class="form-text">Featured products appear on the home page</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" required>
                        <div class="form-text">Upload a high-quality image of the product (max 5MB).</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>