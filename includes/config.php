<?php
// Site Configuration
define('SITE_NAME', 'Volt3dge: Precision in Every Layer');
// For live, change this to 'https://volt3dge.com'
define('SITE_URL', 'https://volt3dge.com'); 
define('ADMIN_EMAIL', 'jayant@volt3dge.com');

// File Upload Settings
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', ['stl', '3mf', 'obj', 'stp', 'step']);
define('STL_UPLOAD_DIR', __DIR__ . '/../uploads/stl_files/');
define('PRODUCT_IMAGE_DIR', __DIR__ . '/../uploads/products/');
define('ORDER_ATTACHMENT_DIR', __DIR__ . '/../uploads/order_attachments/'); // New Directory

// =================================================================
// PHONEPE V2 CONFIGURATION - FILL THIS CAREFULLY
// =================================================================

// 1. CHOOSE THE CORRECT URL FOR YOUR ENVIRONMENT
// For Sandbox/Testing:
// define('PHONEPE_BASE_URL', 'https://api-preprod.phonepe.com/apis/pg-sandbox');
// For Production/Live, comment the line above and uncomment the line below:
define('PHONEPE_BASE_URL', 'https://api.phonepe.com/apis/identity-manager');




// 2. PASTE THE MATCHING CREDENTIALS FROM THE CORRECT DASHBOARD
define('PHONEPE_CLIENT_ID', '');      // Paste the Client ID that matches the URL above
define('PHONEPE_CLIENT_SECRET', ''); // Paste the Client Secret that matches the URL above

// =================================================================

//
//

//
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
if (!file_exists(ORDER_ATTACHMENT_DIR)) {
    mkdir(ORDER_ATTACHMENT_DIR, 0755, true);
}

//