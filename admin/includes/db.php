<?php
require_once 'config.php';

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'voltdgec_3dprint_shop');
define('DB_PASS', 'sFR3ffJbGDLqs5P3wvF7');
define('DB_NAME', 'voltdgec_3dprint_shop');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>