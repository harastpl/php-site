<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $notes = trim($_POST['notes']);
    $stlFile = $_FILES['stl_file'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if ($stlFile['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = 'STL file is required';
    }
    
    if (empty($errors)) {
        // Upload STL file
        $uploadResult = uploadSTLFile($stlFile);
        
        if ($uploadResult['success']) {
            // Save order to database
            try {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'] ?? null, 0, 'pending']);
                $orderId = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, custom_stl, custom_notes) 
                                      VALUES (?, NULL, 1, 0, ?, ?)");
                $stmt->execute([$orderId, $uploadResult['file_name'], $notes]);
                
                // Send email to admin
                $to = ADMIN_EMAIL;
                $subject = 'New Custom 3D Print Order';
                $message = "New custom order received:\n\n";
                $message .= "Order ID: $orderId\n";
                $message .= "Customer Name: $name\n";
                $message .= "Customer Email: $email\n";
                $message .= "Notes: $notes\n";
                $message .= "STL File: " . SITE_URL . "/uploads/stl_files/" . $uploadResult['file_name'] . "\n";
                
                mail($to, $subject, $message);
                
                $_SESSION['success'] = 'Your custom order has been submitted successfully! We will contact you soon with a quote.';
                redirect('index.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $errors[] = $uploadResult['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom 3D Print Order | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4">Custom 3D Print Order</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="custom-order.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stl_file" class="form-label">Upload Your STL File</label>
                        <input type="file" class="form-control" id="stl_file" name="stl_file" accept=".stl" required>
                        <div class="form-text">Max file size: 50MB. Only STL files are accepted.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <div class="form-text">Tell us about your project - material preferences, dimensions, quantity, etc.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Order</button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>