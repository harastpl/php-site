<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : ' Volt3dge: Precision in Every Layer'; ?></title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>

    <!-- Desktop Header -->
    <nav class="navbar navbar-expand-lg desktop-only">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Logo" height="40" class="d-inline-block align-top">
                <?php echo defined('SITE_NAME') ? SITE_NAME : ' Volt3dge'; ?>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="custom-order.php">Custom Order</a></li>
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                        <?php if (function_exists('isAdmin') && isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin/">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Header -->
    <nav class="navbar navbar-dark mobile-only" style="background-color: #008BFF;">
        <div class="container">
            <div class="w-100 d-flex align-items-center">
                <a href="index.php" class="d-inline-flex align-items-center me-2 text-decoration-none">
                    <img src="assets/images/logo.png" alt="Volt3dge logo" style="height: 36px;">
                </a>
                <div class="flex-grow-1 text-center">
                    <a href="index.php" class="text-decoration-none">
                        <div class="h5 mb-0 text-white fw-bold">Volt3dge</div>
                        <div class="small text-white-50">Precision in every layer</div>
                    </a>
                </div>
                <div style="width: 44px;"></div>
            </div>
        </div>
    </nav>

    <div class="container <?php echo (basename($_SERVER['SCRIPT_NAME']) === 'index.php') ? '' : 'mt-2 mt-md-4'; ?>" style="<?php echo (basename($_SERVER['SCRIPT_NAME']) === 'index.php') ? 'margin-top:7px;' : ''; ?>">