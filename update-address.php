<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = trim($_POST['address']);
    
    if (!empty($address)) {
        $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$address, $_SESSION['user_id']]);
        
        $_SESSION['success'] = 'Address updated successfully!';
    } else {
        $_SESSION['error'] = 'Address cannot be empty.';
    }
}

header("Location: orders.php");
exit();
?>