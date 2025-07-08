<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : '3D Print Shop'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Astrospace Font -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #e0e0ff;
            min-height: 100vh;
        }
        .navbar {
            background-color: #1a1a2e !important;
            border-bottom: 1px solid rgba(167, 139, 250, 0.2);
        }
        .navbar-brand {
            color: #a78bfa !important;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(167, 139, 250, 0.5);
        }
        .nav-link {
            color: #e0e0ff !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #a78bfa !important;
            text-shadow: 0 0 5px rgba(167, 139, 250, 0.5);
        }
        .btn-primary {
            background-color: #6d28d9;
            border-color: #6d28d9;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.5);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Logo" height="40" class="d-inline-block align-top me-2">
                <?php echo defined('SITE_NAME') ? SITE_NAME : '3D Print Shop'; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="custom-order.php">Custom Order</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item"><a class="nav-link" href="admin/">Admin Panel</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">