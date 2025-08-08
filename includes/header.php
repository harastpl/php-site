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

    <nav class="navbar navbar-expand-lg d-none d-lg-flex">
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

<nav class="navbar navbar-dark d-lg-none" style="background-color: #008BFF;">
    <div class="container position-relative">

        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo.png" alt="Logo" height="40">
        </a>

        <a href="index.php" class="text-decoration-none position-absolute top-50 start-50 translate-middle">
            <div class="text-center">
                <h1 class="mb-0 fs-4 text-white">Volt3dge</h1>
                <p class="small mb-0" style="color: rgba(255, 255, 255, 0.75);">Precision in every layer</p>
            </div>
        </a>

    </div>
</nav>

    <div class="container mt-4">