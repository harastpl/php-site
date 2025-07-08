<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #e0e0ff;
        }
        .navbar {
            background-color: #1a1a2e !important;
            border-bottom: 1px solid rgba(167, 139, 250, 0.2);
        }
        .sidebar {
            background: rgba(26, 26, 46, 0.9);
            border-right: 1px solid rgba(167, 139, 250, 0.2);
            min-height: calc(100vh - 56px);
        }
        .nav-link {
            color: #e0e0ff !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(167, 139, 250, 0.2);
            color: #a78bfa !important;
        }
        .table {
            background: rgba(26, 26, 46, 0.8);
            color: #e0e0ff;
        }
        .table th {
            border-color: rgba(167, 139, 250, 0.2);
            background: rgba(15, 15, 26, 0.8);
        }
        .table td {
            border-color: rgba(167, 139, 250, 0.1);
        }
        .card {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(167, 139, 250, 0.2);
            color: #e0e0ff;
        }
        .form-control, .form-select {
            background: rgba(15, 15, 26, 0.8);
            border: 1px solid rgba(167, 139, 250, 0.3);
            color: #e0e0ff;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(15, 15, 26, 0.9);
            border-color: #a78bfa;
            box-shadow: 0 0 10px rgba(167, 139, 250, 0.5);
            color: #e0e0ff;
        }
        .btn-primary {
            background-color: #6d28d9;
            border-color: #6d28d9;
        }
        .btn-primary:hover {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../">
                <?php echo SITE_NAME; ?> - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../../">View Site</a>
                <a class="nav-link" href="../../logout.php">Logout</a>
            </div>
        </div>
    </nav>