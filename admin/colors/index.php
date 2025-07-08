<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Handle color addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_color'])) {
    $name = trim($_POST['name']);
    $hex_code = trim($_POST['hex_code']);
    
    if (!empty($name) && !empty($hex_code)) {
        $stmt = $pdo->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)");
        $stmt->execute([$name, $hex_code]);
        $_SESSION['success'] = 'Color added successfully!';
    }
    redirect('index.php');
}

// Handle color deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM colors WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Color deleted successfully!';
    redirect('index.php');
}

// Handle color toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE colors SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Color status updated!';
    redirect('index.php');
}

// Get all colors
$stmt = $pdo->query("SELECT * FROM colors ORDER BY name");
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Colors | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin-sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Colors</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Color Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Color</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-4">
                                <label for="name" class="form-label">Color Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="hex_code" class="form-label">Hex Code</label>
                                <input type="color" class="form-control form-control-color" id="hex_code" name="hex_code" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_color" class="btn btn-primary d-block">Add Color</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Colors List -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Preview</th>
                                <th>Name</th>
                                <th>Hex Code</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($colors as $color): ?>
                            <tr>
                                <td><?php echo $color['id']; ?></td>
                                <td>
                                    <div style="width: 30px; height: 30px; background-color: <?php echo $color['hex_code']; ?>; border: 1px solid #ccc; border-radius: 50%;"></div>
                                </td>
                                <td><?php echo htmlspecialchars($color['name']); ?></td>
                                <td><?php echo htmlspecialchars($color['hex_code']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $color['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $color['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($color['created_at'])); ?></td>
                                <td>
                                    <a href="index.php?toggle=<?php echo $color['id']; ?>" 
                                       class="btn btn-sm btn-<?php echo $color['is_active'] ? 'warning' : 'success'; ?>">
                                        <?php echo $color['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="index.php?delete=<?php echo $color['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this color?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>