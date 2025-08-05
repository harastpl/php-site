<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($id);
$categories = getCategories();
$colors = getColors();
$productImages = getProductImages($id);

// Get product colors
$stmt = $pdo->prepare("SELECT color_id FROM product_colors WHERE product_id = ?");
$stmt->execute([$id]);
$productColors = array_column($stmt->fetchAll(), 'color_id');

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $delivery_charge = trim($_POST['delivery_charge']);
    $delivery_charge_threshold = (int)$_POST['delivery_charge_threshold'];
    $delivery_charge_alt = trim($_POST['delivery_charge_alt']);
    $stock = (int)$_POST['stock'];
    $low_stock_threshold = (int)$_POST['low_stock_threshold'];
    $category_id = (int)$_POST['category_id'];
    $is_featured = (int)$_POST['is_featured'];
    $enable_colors = isset($_POST['enable_colors']) ? 1 : 0;
    $selected_colors = isset($_POST['colors']) ? $_POST['colors'] : [];
    $images = $_FILES['images'];
    
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
    
    if (empty($category_id)) {
        $errors[] = 'Please select a category';
    }
    
    if (empty($errors)) {
        // Handle new image uploads if provided
        if (!empty($images['name'][0])) {
            $uploadedImages = [];
            $target_dir = PRODUCT_IMAGE_DIR;
            
            for ($i = 0; $i < count($images['name']); $i++) {
                if ($images['error'][$i] == UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . $i . '_' . basename($images["name"][$i]);
                    $target_file = $target_dir . $file_name;
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    
                    // Validate image
                    $check = getimagesize($images["tmp_name"][$i]);
                    if ($check === false) {
                        $errors[] = 'File ' . $images["name"][$i] . ' is not an image.';
                        continue;
                    }
                    
                    if ($images["size"][$i] > 5000000) {
                        $errors[] = 'File ' . $images["name"][$i] . ' is too large. Max 5MB allowed.';
                        continue;
                    }
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($imageFileType, $allowed_types)) {
                        $errors[] = 'File ' . $images["name"][$i] . ' has invalid format.';
                        continue;
                    }
                    
                    if (move_uploaded_file($images["tmp_name"][$i], $target_file)) {
                        $uploadedImages[] = [
                            'file_name' => $file_name,
                            'is_primary' => $i === 0 ? 1 : 0,
                            'sort_order' => $i
                        ];
                    }
                }
            }
            
            if (!empty($uploadedImages) && empty($errors)) {
                // Delete old images
                foreach ($productImages as $oldImage) {
                    $oldImagePath = $target_dir . $oldImage['image_path'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                // Delete old image records
                $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
                $stmt->execute([$id]);
                
                // Insert new images
                foreach ($uploadedImages as $image) {
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $image['file_name'], $image['is_primary'], $image['sort_order']]);
                }
                
                // Update main product image
                $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                $stmt->execute([$uploadedImages[0]['file_name'], $id]);
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, delivery_charge = ?, delivery_charge_threshold = ?, delivery_charge_alt = ?, stock = ?, low_stock_threshold = ?, category_id = ?, is_featured = ?, enable_colors = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $delivery_charge, $delivery_charge_threshold, $delivery_charge_alt, $stock, $low_stock_threshold, $category_id, $is_featured, $enable_colors, $id]);
                
                // Update product colors
                $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
                $stmt->execute([$id]);
                
                if ($enable_colors && !empty($selected_colors)) {
                    foreach ($selected_colors as $color_id) {
                        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?, ?)");
                        $stmt->execute([$id, $color_id]);
                    }
                }
                
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
    <link href="../../assets/css/styles.css" rel="stylesheet">
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="enable_colors" name="enable_colors" <?php echo $product['enable_colors'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_colors">Enable Color Selection</label>
                                        </div>
                                        <div class="form-text">Allow customers to choose colors for this product</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="is_featured" class="form-label">Featured Product</label>
                                        <select class="form-select" id="is_featured" name="is_featured">
                                            <option value="0" <?php echo !$product['is_featured'] ? 'selected' : ''; ?>>No</option>
                                            <option value="1" <?php echo $product['is_featured'] ? 'selected' : ''; ?>>Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="color-selection" style="display: <?php echo $product['enable_colors'] ? 'block' : 'none'; ?>;">
                                <label class="form-label">Available Colors</label>
                                <div class="row">
                                    <?php foreach ($colors as $color): ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="colors[]" value="<?php echo $color['id']; ?>" id="color_<?php echo $color['id']; ?>" <?php echo in_array($color['id'], $productColors) ? 'checked' : ''; ?>>
                                                <label class="form-check-label d-flex align-items-center" for="color_<?php echo $color['id']; ?>">
                                                    <span class="color-preview me-2" style="background-color: <?php echo $color['hex_code']; ?>; width: 20px; height: 20px; border-radius: 50%; border: 1px solid #ccc;"></span>
                                                    <?php echo htmlspecialchars($color['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="delivery_charge" class="form-label">Delivery Charge (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge" value="<?php echo htmlspecialchars($product['delivery_charge']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="delivery_charge_threshold" class="form-label">Delivery Charge Threshold (Quantity)</label>
                                        <input type="number" class="form-control" id="delivery_charge_threshold" name="delivery_charge_threshold" value="<?php echo htmlspecialchars($product['delivery_charge_threshold']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="delivery_charge_alt" class="form-label">Alternate Delivery Charge (₹)</label>
                                        <input type="number" step="0.01" class="form-control" id="delivery_charge_alt" name="delivery_charge_alt" value="<?php echo htmlspecialchars($product['delivery_charge_alt']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Images</label>
                                <div class="mb-2" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (!empty($productImages)): ?>
                                        <?php foreach ($productImages as $image): ?>
                                            <div class="mb-2">
                                                <img src="../../uploads/products/<?php echo $image['image_path']; ?>" 
                                                     alt="Product image" class="img-fluid" style="max-height: 100px;">
                                                <?php if ($image['is_primary']): ?>
                                                    <small class="text-primary d-block">Primary Image</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <img src="../../uploads/products/<?php echo $product['image']; ?>" 
                                             alt="Current product image" class="img-fluid" style="max-height: 200px;">
                                    <?php endif; ?>
                                </div>
                                <label for="images" class="form-label">New Images (optional)</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple>
                                <div class="form-text">Leave empty to keep current images. Uploading new images will replace all existing images. Max 5MB each.</div>
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
    <script>
        document.getElementById('enable_colors').addEventListener('change', function() {
            const colorSelection = document.getElementById('color-selection');
            if (this.checked) {
                colorSelection.style.display = 'block';
            } else {
                colorSelection.style.display = 'none';
            }
        });
    </script>
</body>
</html>