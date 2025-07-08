<?php
// Site Configuration
define('SITE_NAME', 'Volt3dge: Precision in Every Layer');
define('SITE_URL', 'https://volt3dge.com');
define('ADMIN_EMAIL', 'jayant@volt3dge.com');

// File Upload Settings
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 50MB
define('ALLOWED_STL_TYPES', ['application/octet-stream', 'application/sla', 'application/vnd.ms-pki.stl']);
define('STL_UPLOAD_DIR', __DIR__ . '/../uploads/stl_files/');
define('PRODUCT_IMAGE_DIR', __DIR__ . '/../uploads/products/');

// Display Errors (set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>