<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $phone = trim($_POST['phone']);
    
    $errors = [];
    
    // Validate inputs
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($city)) {
        $errors[] = 'City is required';
    }
    
    if (empty($state)) {
        $errors[] = 'State is required';
    }
    
    if (empty($pincode) || !preg_match('/^\d{6}$/', $pincode)) {
        $errors[] = 'Invalid pincode. Please enter a 6-digit pincode';
    }
    
    if (empty($phone) || !preg_match('/^\+\d{1,3}\d{10}$/', $phone)) {
        $errors[] = 'Invalid phone number. Please enter a valid phone number with country code (+91 followed by 10 digits)';
    }
    
    if (empty($errors)) {
        $full_address = json_encode([
            'full_name' => $full_name,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'phone' => $phone
        ]);
        
        $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$full_address, $_SESSION['user_id']]);
        
        $_SESSION['success'] = 'Address updated successfully!';
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

header("Location: " . ($_GET['redirect'] ?? 'orders.php'));
exit();
?>