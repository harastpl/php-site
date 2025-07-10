<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require login for custom orders
requireLogin();

$colors = getColors();
$materials = getMaterials();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = trim($_POST['notes']);
    $infill = (int)$_POST['infill'];
    $layer_height = (float)$_POST['layer_height'];
    $support_needed = isset($_POST['support_needed']) ? 1 : 0;
    $color_id = (int)$_POST['color_id'];
    $material_id = (int)$_POST['material_id'];
    $quantity = (int)$_POST['quantity'];
    $file = $_FILES['design_file'];
    
    // Validate inputs
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Design file is required';
    }
    
    if ($infill < 5 || $infill > 100 || $infill % 5 != 0) {
        $errors[] = 'Infill must be between 5-100% in multiples of 5';
    }
    
    if ($layer_height < 0.08 || $layer_height > 2.4) {
        $errors[] = 'Layer height must be between 0.08mm and 2.4mm';
    }
    
    if ($quantity < 1) {
        $errors[] = 'Quantity must be at least 1';
    }
    
    if (empty($errors)) {
        $uploadResult = uploadFile($file, ALLOWED_FILE_TYPES);
        
        if ($uploadResult['success']) {
            try {
                // Calculate estimated price (this would be refined based on actual file analysis)
                $basePrice = SETUP_FEE + (BASE_PRICE_PER_GRAM * 50 * $quantity); // Assuming 50g average
                
                // Use base price (no material multiplier)
                $totalPrice = $basePrice;
                
                // Apply bulk discount
                $discount = calculateBulkDiscount($quantity, $totalPrice);
                
                // Create order
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, discount_amount, final_total, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $totalPrice, $discount['amount'], $discount['final_total']]);
                $orderId = $pdo->lastInsertId();
                
                // Create order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, quantity, price, custom_stl, custom_notes, infill_percentage, layer_height, support_needed, color_id, material_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $quantity, $discount['final_total'], $uploadResult['file_name'], $notes, $infill, $layer_height, $support_needed, $color_id, $material_id]);
                
                // Send notification email
                $colorName = '';
                $materialName = '';
                
                $stmt = $pdo->prepare("SELECT name FROM colors WHERE id = ?");
                $stmt->execute([$color_id]);
                $colorResult = $stmt->fetch();
                if ($colorResult) $colorName = $colorResult['name'];
                
                $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
                $stmt->execute([$material_id]);
                $materialResult = $stmt->fetch();
                if ($materialResult) $materialName = $materialResult['name'];
                
                $subject = 'New Custom 3D Print Order - #' . $orderId;
                $message = "New custom order received:\n\n";
                $message .= "Order ID: #$orderId\n";
                $message .= "Customer: " . $_SESSION['username'] . " (" . $_SESSION['email'] . ")\n";
                $message .= "Quantity: $quantity\n";
                $message .= "Material: $materialName\n";
                $message .= "Color: $colorName\n";
                $message .= "Infill: {$infill}%\n";
                $message .= "Layer Height: {$layer_height}mm\n";
                $message .= "Support Needed: " . ($support_needed ? 'Yes' : 'No') . "\n";
                $message .= "Estimated Total: " . formatCurrency($discount['final_total']) . "\n";
                $message .= "Notes: $notes\n";
                $message .= "File: " . SITE_URL . "/uploads/stl_files/" . $uploadResult['file_name'] . "\n";
                
                sendEmailNotification(ADMIN_EMAIL, $subject, $message);
                
                $_SESSION['success'] = 'Your custom order has been submitted successfully! Order ID: #' . $orderId . '. We will contact you soon with final pricing and timeline.';
                redirect('orders.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            color: #e0e0ff;
            min-height: 100vh;
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
        .color-option {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin: 5px;
            border: 2px solid #fff;
            cursor: pointer;
        }
        .color-option.selected {
            border-color: #a78bfa;
            box-shadow: 0 0 10px rgba(167, 139, 250, 0.8);
        }
        .infill-slider {
            width: 100%;
        }
        .specification-card {
            background: rgba(26, 26, 46, 0.6);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="mb-4 glow">Custom 3D Print Order</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <!-- File Upload Section -->
                    <div class="specification-card">
                        <h4 class="mb-3">Upload Your Design</h4>
                        <div class="mb-3">
                            <label for="design_file" class="form-label">Design File *</label>
                            <input type="file" class="form-control" id="design_file" name="design_file" 
                                   accept=".stl,.3mf,.obj,.stp,.step" required>
                            <div class="form-text">Supported formats: STL, 3MF, OBJ, STP, STEP (Max: 50MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" value="1" required>
                        </div>
                    </div>

                    <!-- Print Specifications -->
                    <div class="specification-card">
                        <h4 class="mb-3">Print Specifications</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="infill" class="form-label">Infill Percentage: <span id="infill-value">20</span>%</label>
                                    <input type="range" class="form-range infill-slider" id="infill" name="infill" 
                                           min="5" max="100" step="5" value="20" 
                                           oninput="document.getElementById('infill-value').textContent = this.value">
                                    <div class="form-text">Higher infill = stronger but more expensive</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="layer_height" class="form-label">Layer Height (mm)</label>
                                    <select class="form-select" id="layer_height" name="layer_height" required>
                                        <option value="0.08">0.08mm (Ultra Fine)</option>
                                        <option value="0.12">0.12mm (Fine)</option>
                                        <option value="0.16">0.16mm (Good)</option>
                                        <option value="0.2" selected>0.2mm (Recommended)</option>
                                        <option value="0.24">0.24mm (Fast)</option>
                                        <option value="0.28">0.28mm (Draft)</option>
                                    </select>
                                    <div class="form-text">Recommended: 0.2mm (Lower = better quality, slower print)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="support_needed" name="support_needed">
                                <label class="form-check-label" for="support_needed">
                                    Add Support Material
                                </label>
                                <div class="form-text">Check if your design has overhangs or bridges</div>
                            </div>
                        </div>
                    </div>

                    <!-- Material Selection -->
                    <div class="specification-card">
                        <h4 class="mb-3">Material & Color</h4>
                        
                        <div class="mb-3">
                            <label for="material_id" class="form-label">Material *</label>
                            <select class="form-select" id="material_id" name="material_id" required>
                                <option value="">Select Material</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?php echo $material['id']; ?>" 
                                            <?php echo (strtolower($material['name']) == 'pla') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($material['name']); ?> 
                                        (<?php echo htmlspecialchars($material['description']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">PLA is recommended for beginners</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Color *</label>
                            <div class="color-selection">
                                <?php foreach ($colors as $color): ?>
                                    <label class="color-option-label">
                                        <input type="radio" name="color_id" value="<?php echo $color['id']; ?>" 
                                               style="display: none;" required>
                                        <div class="color-option" 
                                             style="background-color: #<?php echo $color['hex_code']; ?>"
                                             title="<?php echo htmlspecialchars($color['name']); ?>"
                                             onclick="selectColor(this)"></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Click to select color</div>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="specification-card">
                        <h4 class="mb-3">Additional Information</h4>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="Any special requirements, finishing preferences, or other notes..."></textarea>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Submit Custom Order</button>
                        <a href="index.php" class="btn btn-secondary btn-lg ms-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function selectColor(element) {
            // Remove selected class from all color options
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the corresponding radio button
            element.parentElement.querySelector('input[type="radio"]').checked = true;
        }
    </script>
</body>
</html>