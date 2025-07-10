<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Handle material addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_material'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO materials (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
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
                            <div class="col-md-4">
                                <label for="name" class="form-label">Material Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">e.g., PLA (Recommended), ABS, PETG, TPU</div>
                            </div>
                            <div class="col-md-6">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description">
                                <div class="form-text">Brief description of material properties</div>
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
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><?php echo $material['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($material['name']); ?>
                                    <?php if (stripos($material['name'], 'pla') !== false): ?>
                                        <span class="badge bg-info">Recommended</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($material['description']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $material['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $material['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
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