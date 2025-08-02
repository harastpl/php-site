<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$categories = getCategories();

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
    $images = $_FILES['images'];
    $stl_file = $_FILES['stl_file'];
    
    $errors = [];
    $stl_file_name = null;

    // Handle STL upload if provided
    if ($stl_file['error'] != UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($stl_file, ALLOWED_FILE_TYPES);
        if ($uploadResult['success']) {
            $stl_file_name = $uploadResult['file_name'];
        } else {
            $errors[] = 'STL file upload error: ' . $uploadResult['message'];
        }
    }
    
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
    
    if (empty($images['name'][0])) {
        $errors[] = 'At least one product image is required';
    }
    
    if (empty($errors)) {
        // Handle multiple image uploads
        $uploadedImages = [];
        $target_dir = PRODUCT_IMAGE_DIR;
        
        for ($i = 0; $i < count($images['name']); $i++) {
            if ($images['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = time() . '_' . $i . '_' . basename($images["name"][$i]);
                $target_file = $target_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
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
                    $errors[] = 'File ' . $images["name"][$i] . ' has invalid format. Only JPG, JPEG, PNG & GIF files are allowed.';
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
            try {
                // Insert product into database
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, delivery_charge, delivery_charge_threshold, delivery_charge_alt, stock, low_stock_threshold, category_id, is_featured, image, stl_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $delivery_charge, $delivery_charge_threshold, $delivery_charge_alt, $stock, $low_stock_threshold, $category_id, $is_featured, $uploadedImages[0]['file_name'], $stl_file_name]);
                $product_id = $pdo->lastInsertId();
                
                // Insert product images
                foreach ($uploadedImages as $image) {
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$product_id, $image['file_name'], $image['is_primary'], $image['sort_order']]);
                }
                
                $_SESSION['success'] = 'Product added successfully!';
                redirect('index.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } elseif (empty($uploadedImages)) {
            $errors[] = 'No images were uploaded successfully.';
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
    <link href="../../assets/css/styles.css" rel="stylesheet">
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
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Alert Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="10" required>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="delivery_charge" class="form-label">Delivery Charge (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="delivery_charge" name="delivery_charge">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="delivery_charge_threshold" class="form-label">Delivery Charge Threshold (Quantity)</label>
                                <input type="number" class="form-control" id="delivery_charge_threshold" name="delivery_charge_threshold">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="delivery_charge_alt" class="form-label">Alternate Delivery Charge (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="delivery_charge_alt" name="delivery_charge_alt">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
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
                        <label for="images" class="form-label">Product Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple required>
                        <div class="form-text">Upload high-quality images of the product (max 5MB each). First image will be the primary image.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stl_file" class="form-label">Product STL File (Optional)</label>
                        <input type="file" class="form-control" id="stl_file" name="stl_file" accept=".stl,.3mf,.obj,.stp,.step">
                        <div class="form-text">Upload the STL file for this product if it's a standard 3D model.</div>
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