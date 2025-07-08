<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

// Handle discount addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_discount'])) {
    $min_quantity = (int)$_POST['min_quantity'];
    $discount_percentage = (float)$_POST['discount_percentage'];
    
    if ($min_quantity > 0 && $discount_percentage > 0) {
        $stmt = $pdo->prepare("INSERT INTO bulk_discounts (min_quantity, discount_percentage) VALUES (?, ?)");
        $stmt->execute([$min_quantity, $discount_percentage]);
        $_SESSION['success'] = 'Bulk discount added successfully!';
    }
    redirect('index.php');
}

// Handle discount deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM bulk_discounts WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Bulk discount deleted successfully!';
    redirect('index.php');
}

// Handle discount toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE bulk_discounts SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Discount status updated!';
    redirect('index.php');
}

// Get all discounts
$stmt = $pdo->query("SELECT * FROM bulk_discounts ORDER BY min_quantity");
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bulk Discounts | <?php echo SITE_NAME; ?></title>
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
                    <h1 class="h2">Manage Bulk Discounts</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Discount Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Bulk Discount</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-4">
                                <label for="min_quantity" class="form-label">Minimum Quantity</label>
                                <input type="number" class="form-control" id="min_quantity" name="min_quantity" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label for="discount_percentage" class="form-label">Discount Percentage</label>
                                <input type="number" step="0.01" class="form-control" id="discount_percentage" name="discount_percentage" min="0.01" max="100" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" name="add_discount" class="btn btn-primary d-block">Add Discount</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Discounts List -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Minimum Quantity</th>
                                <th>Discount Percentage</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discounts as $discount): ?>
                            <tr>
                                <td><?php echo $discount['id']; ?></td>
                                <td><?php echo $discount['min_quantity']; ?> items</td>
                                <td><?php echo $discount['discount_percentage']; ?>%</td>
                                <td>
                                    <span class="badge bg-<?php echo $discount['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $discount['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($discount['created_at'])); ?></td>
                                <td>
                                    <a href="index.php?toggle=<?php echo $discount['id']; ?>" 
                                       class="btn btn-sm btn-<?php echo $discount['is_active'] ? 'warning' : 'success'; ?>">
                                        <?php echo $discount['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="index.php?delete=<?php echo $discount['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this discount?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info">
                    <h6>How Bulk Discounts Work:</h6>
                    <p>Customers will automatically receive the highest applicable discount based on their order quantity. For example, if you have discounts for 5+ items (5%) and 10+ items (10%), a customer ordering 12 items will receive the 10% discount.</p>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>