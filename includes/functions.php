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

// Get all products with stock info
function getProducts($limit = null, $checkStock = false, $featuredOnly = false) {
    global $pdo;
    $sql = "SELECT * FROM products";
    $conditions = [];
    if ($checkStock) {
        $conditions[] = "stock > 0";
    }
    if ($featuredOnly) {
        $conditions[] = "is_featured = 1";
    }
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get featured products only
function getFeaturedProducts($limit = null) {
    return getProducts($limit, false, true);
}

// Get single product
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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