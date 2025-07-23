<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('index.php');
}

// Fetch the category
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = 'Category not found.';
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $enable_text_field = isset($_POST['enable_text_field']) ? 1 : 0;
    $text_field_label = trim($_POST['text_field_label']);
    $text_field_required = isset($_POST['text_field_required']) ? 1 : 0;
    $enable_file_upload = isset($_POST['enable_file_upload']) ? 1 : 0;
    $file_upload_label = trim($_POST['file_upload_label']);
    $file_upload_required = isset($_POST['file_upload_required']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['error'] = 'Category name cannot be empty.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET name = ?, description = ?, enable_text_field = ?, text_field_label = ?, text_field_required = ?, 
                enable_file_upload = ?, file_upload_label = ?, file_upload_required = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $description, $enable_text_field, $text_field_label, $text_field_required,
            $enable_file_upload, $file_upload_label, $file_upload_required, $id
        ]);
        $_SESSION['success'] = 'Category updated successfully!';
        redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin-sidebar2.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Category: <?php echo htmlspecialchars($category['name']); ?></h1>
                </div>

                <form method="post">
                    <div class="card mb-4">
                        <div class="card-header">Basic Details</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($category['description']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">Custom Text Field</div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_text_field" name="enable_text_field" <?php echo $category['enable_text_field'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_text_field">Enable Text Field for Products in this Category</label>
                            </div>
                            <div class="mb-3">
                                <label for="text_field_label" class="form-label">Text Field Label</label>
                                <input type="text" class="form-control" id="text_field_label" name="text_field_label" value="<?php echo htmlspecialchars($category['text_field_label']); ?>">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="text_field_required" name="text_field_required" <?php echo $category['text_field_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="text_field_required">Make this text field required</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">Custom File Upload</div>
                        <div class="card-body">
                             <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_file_upload" name="enable_file_upload" <?php echo $category['enable_file_upload'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_file_upload">Enable File Upload for Products in this Category</label>
                            </div>
                            <div class="mb-3">
                                <label for="file_upload_label" class="form-label">File Upload Label</label>
                                <input type="text" class="form-control" id="file_upload_label" name="file_upload_label" value="<?php echo htmlspecialchars($category['file_upload_label']); ?>">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="file_upload_required" name="file_upload_required" <?php echo $category['file_upload_required'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="file_upload_required">Make this file upload required</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </form>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>