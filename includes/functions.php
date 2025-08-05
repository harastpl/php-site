<?php
require_once 'config.php';
require_once 'db.php';

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message function
function flash($name = '', $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name.'_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name.'_class']) ? $_SESSION[$name.'_class'] : '';
            echo '<div class="'.$class.'" id="msg-flash">'.$_SESSION[$name].'</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name.'_class']);
        }
    }
}

// Upload file function for multiple formats
function uploadFile($file, $allowedTypes = ['stl', '3mf', 'obj', 'stp', 'step']) {
    $target_dir = STL_UPLOAD_DIR;
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file type is allowed
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Sorry, only ' . implode(', ', $allowedTypes) . ' files are allowed.'];
    }

    // Check file size
    if ($file["size"] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Sorry, your file is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.'];
    }

    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Sorry, there was an error uploading your file.'];
    }
}

// Get all products with stock info and image gallery
function getProducts($limit = null, $checkStock = false, $featuredOnly = false, $categoryId = null, $search = null, $sortBy = 'created_at', $sortOrder = 'DESC') {
    global $pdo;
    $sql = "SELECT p.*, c.name as category_name, 
                   (SELECT GROUP_CONCAT(pi.image_path ORDER BY pi.is_primary DESC, pi.sort_order ASC) 
                    FROM product_images pi WHERE pi.product_id = p.id) as image_gallery
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id";
    $conditions = [];
    $params = [];
    
    if ($checkStock) {
        $conditions[] = "p.stock > 0";
    }
    if ($featuredOnly) {
        $conditions[] = "p.is_featured = 1";
    }
    if ($categoryId) {
        $conditions[] = "p.category_id = ?";
        $params[] = $categoryId;
    }
    if ($search) {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add sorting
    $allowedSorts = ['name', 'price', 'created_at'];
    $allowedOrders = ['ASC', 'DESC'];
    
    if (in_array($sortBy, $allowedSorts)) {
        $sortColumn = $sortBy === 'created_at' ? 'p.created_at' : "p.$sortBy";
    } else {
        $sortColumn = 'p.created_at';
    }
    
    if (!in_array($sortOrder, $allowedOrders)) {
        $sortOrder = 'DESC';
    }
    
    $sql .= " ORDER BY $sortColumn $sortOrder";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all categories
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product images
function getProductImages($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get primary product image
function getPrimaryProductImage($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return $result ? $result['image_path'] : null;
}

// Get featured products only
function getFeaturedProducts($limit = null) {
    return getProducts($limit, false, true);
}

// Get single product
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name, 
               c.enable_text_field, c.text_field_label, c.text_field_required,
               c.enable_file_upload, c.file_upload_label, c.file_upload_required
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get product colors
function getProductColors($productId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.* FROM colors c 
        JOIN product_colors pc ON c.id = pc.color_id 
        WHERE pc.product_id = ? AND c.is_active = 1 
        ORDER BY c.name
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all colors
function getColors() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM colors WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all materials
function getMaterials() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM materials WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get bulk discounts
function getBulkDiscounts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM bulk_discounts WHERE is_active = 1 ORDER BY min_quantity");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate bulk discount
function calculateBulkDiscount($quantity, $total) {
    $discounts = getBulkDiscounts();
    $discountPercentage = 0;
    
    foreach ($discounts as $discount) {
        if ($quantity >= $discount['min_quantity']) {
            $discountPercentage = $discount['discount_percentage'];
        }
    }
    
    $discountAmount = ($total * $discountPercentage) / 100;
    return [
        'percentage' => $discountPercentage,
        'amount' => $discountAmount,
        'final_total' => $total - $discountAmount
    ];
}

// Check low stock products
function getLowStockProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM products WHERE stock <= low_stock_threshold ORDER BY stock ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Generate order ID
function generateOrderId() {
    return 'ORD' . time() . rand(100, 999);
}

// Send email notification
function sendEmailNotification($to, $subject, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}
?>