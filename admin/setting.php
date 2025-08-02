<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['enable_delivery_charges'])) {
        update_setting('enable_delivery_charges', 1);
    } else {
        update_setting('enable_delivery_charges', 0);
    }
    
    $_SESSION['success'] = 'Settings updated successfully!';
    redirect('index.php');
}
?>