<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Handle material addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_material'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price_multiplier = (float)$_POST['price_multiplier'];
    
    if (!empty($name) && $price_multiplier > 0) {
        $stmt = $pdo->prepare("INSERT INTO materials (name, description, price_multiplier) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $price_multiplier]);
        $_SESSION['success'] = 'Material added successfully!';
    }
    redirect('index.php');
}

// Handle material deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Material deleted successfully!';
    redirect('index.php');
}

// Handle material toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE materials SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Material status updated!';
    redirect('index.php');
}

// Get all materials
$stmt = $pdo->query("SELECT * FROM materials ORDER BY name");
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials | <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Materials</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Material Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Material</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-3">
                                <label for="name" class="form-label">Material Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description">
                            </div>
                            <div class="col-md-3">
                                <label for="price_multiplier" class="form-label">Price Multiplier</label>
                                <input type="number" step="0.01" class="form-control" id="price_multiplier" name="price_multiplier" value="1.00" required>
                                <div class="form-text">1.00 = base price, 1.20 = 20% increase</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_material" class="btn btn-primary d-block">Add Material</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price Multiplier</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?php echo $material['id']; ?></td>
                                <td><?php echo htmlspecialchars($material['name']); ?></td>
                                <td><?php echo htmlspecialchars($material['description']); ?></td>
                                <td>
                                    <?php echo $material['price_multiplier']; ?>x
                                    <?php if ($material['price_multiplier'] != 1.0): ?>
                                        <small class="text-muted">
                                            (<?php echo number_format(($material['price_multiplier'] - 1) * 100); ?>% 
                                            <?php echo $material['price_multiplier'] > 1 ? 'increase' : 'decrease'; ?>)
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $material['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $material['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($material['created_at'])); ?></td>
                                <td>
                                    <a href="index.php?toggle=<?php echo $material['id']; ?>" 
                                       class="btn btn-sm btn-<?php echo $material['is_active'] ? 'warning' : 'success'; ?>">
                                        <?php echo $material['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="index.php?delete=<?php echo $material['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this material?')">Delete</a>
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