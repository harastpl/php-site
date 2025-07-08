<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Cart functionality placeholder
// This can be expanded later if needed for pre-made products

requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <h2 class="glow">Shopping Cart</h2>
        <div class="text-center py-5">
            <p>Cart functionality coming soon!</p>
            <p>For now, please use our <a href="custom-order.php">Custom Order</a> system.</p>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>