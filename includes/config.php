<?php
// Site Configuration
define('SITE_NAME', 'Volt3dge: Precision in Every Layer');
define('SITE_URL', 'http://localhost/3d-site');
define('ADMIN_EMAIL', 'jayant@volt3dge.com');

// File Upload Settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['stl', '3mf', 'obj', 'stp', 'step']);
define('STL_UPLOAD_DIR', __DIR__ . '/../uploads/stl_files/');
define('PRODUCT_IMAGE_DIR', __DIR__ . '/../uploads/products/');

// PhonePe Configuration
define('PHONEPE_MERCHANT_ID', 'YOUR_MERCHANT_ID'); // Replace with your PhonePe Merchant ID
define('PHONEPE_SALT_KEY', 'YOUR_SALT_KEY'); // Replace with your PhonePe Salt Key
define('PHONEPE_SALT_INDEX', 1);
define('PHONEPE_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox'); // Use https://api.phonepe.com/apis/hermes for production

// Pricing Configuration
define('BASE_PRICE_PER_GRAM', 2.50);
define('SETUP_FEE', 50.00);

// Display Errors (set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create upload directories if they don't exist
if (!file_exists(STL_UPLOAD_DIR)) {
    mkdir(STL_UPLOAD_DIR, 0755, true);
}
if (!file_exists(PRODUCT_IMAGE_DIR)) {
    mkdir(PRODUCT_IMAGE_DIR, 0755, true);
}
?>