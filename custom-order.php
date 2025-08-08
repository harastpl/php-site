<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email_functions.php';

// Require login for custom orders
requireLogin();

$colors = getColors();
$materials = getMaterials();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = trim($_POST['notes']);
    $print_type = $_POST['print_type'];
    $infill = (int)$_POST['infill'];
    $layer_height = (float)$_POST['layer_height'];
    $support_needed = isset($_POST['support_needed']) ? 1 : 0;
    $color_id = (int)$_POST['color_id'];
    $material_id = (int)$_POST['material_id'];
    $quantity = (int)$_POST['quantity'];
    $files = $_FILES['design_file'];
    
    // Validate inputs
    if (empty($files['name'][0])) {
        $errors[] = 'Design file is required';
    }
    
    if (empty($color_id)) {
        $errors[] = 'Please select a color';
    }
    
    if (empty($material_id)) {
        $errors[] = 'Please select a material';
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
        $uploadedFiles = [];
        $allowedTypes = array_merge(ALLOWED_FILE_TYPES, ['txt']);
        
        // Handle multiple file uploads
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i]
                ];
                
                $uploadResult = uploadFile($file, $allowedTypes);
                if ($uploadResult['success']) {
                    $uploadedFiles[] = $uploadResult['file_name'];
                } else {
                    $errors[] = 'File ' . $files['name'][$i] . ': ' . $uploadResult['message'];
                }
            }
        }
        
        if (!empty($uploadedFiles) && empty($errors)) {
            try {
                // Calculate estimated price (this would be refined based on actual file analysis)
                // Set initial price to 0 for admin review
                $totalPrice = 0;
                $discount = ['amount' => 0, 'final_total' => 0];
                
                // Create order
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, discount_amount, final_total, status, payment_status) VALUES (?, ?, ?, ?, 'pending', 'pending')");
                $stmt->execute([$_SESSION['user_id'], $totalPrice, $discount['amount'], $discount['final_total']]);
                $orderId = $pdo->lastInsertId();
                
                // Create order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, quantity, price, custom_stl, custom_notes, infill_percentage, layer_height, support_needed, color_id, material_id, print_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $quantity, 0, implode(',', $uploadedFiles), $notes, $infill, $layer_height, $support_needed, $color_id, $material_id, $print_type]);
                
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
                
                // Send order confirmation email to customer and admin
                $orderDetails = [
                    'status' => 'pending',
                    'final_total' => 0
                ];
                sendOrderConfirmationEmail($_SESSION['email'], $orderId, $orderDetails);
                
                $_SESSION['success'] = 'Your custom order has been submitted successfully! Order ID: #' . $orderId . '. We will review your order and contact you with pricing details.';
                redirect('orders.php');
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
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
                    <div class="specification-card">
                        <h4 class="mb-3">Print Technology</h4>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="print_type" id="fdm" value="FDM" checked onchange="updateMaterialOptions()">
                                    <label class="form-check-label" for="fdm">
                                        <strong>FDM (Fused Deposition Modeling)</strong><br>
                                        <small class="text-muted">Standard 3D printing with filaments</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="print_type" id="sla" value="SLA" onchange="updateMaterialOptions()">
                                    <label class="form-check-label" for="sla">
                                        <strong>SLA (Stereolithography)</strong><br>
                                        <small class="text-muted">High-resolution resin printing</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="specification-card">
                        <h4 class="mb-3">Upload Your Design</h4>
                        <div class="mb-3">
                            <label for="design_file" class="form-label">Design File *</label>
                            <input type="file" class="form-control" id="design_file" name="design_file[]" 
                                   accept=".stl,.3mf,.obj,.stp,.step,.zip,.rar,.7z" multiple required>
                            <div class="form-text">Supported formats: STL, 3MF, OBJ, STP, STEP, ZIP (Max: 50MB each). You can upload multiple files.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" value="1" required>
                        </div>
                    </div>

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
                                <input class="form-check-input" type="checkbox" id="support_needed" name="support_needed" checked>
                                <label class="form-check-label" for="support_needed">
                                    Add Support Material
                                </label>
                                <div class="form-text">Check if your design has overhangs or bridges</div>
                            </div>
                        </div>
                    </div>

                    <div class="specification-card">
                        <h4 class="mb-3">Material & Color</h4>
                        
                        <div class="mb-3">
                            <label for="material_id" class="form-label">Material *</label>
                            <select class="form-select" id="material_id" name="material_id" required>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                            <div class="form-text">PLA is recommended for beginners</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Color (Default White)*</label>
                            <div class="color-selection">
                                <?php foreach ($colors as $color): ?>
                                    <label class="color-option-label">
                                        <input type="radio" name="color_id" value="<?php echo $color['id']; ?>" 
                                               style="display: none;" required <?php echo (strtolower($color['name']) == 'white') ? 'checked' : ''; ?>>
                                        <div class="color-option <?php echo (strtolower($color['name']) == 'white') ? 'selected' : ''; ?>" 
                                             style="background-color: <?php echo $color['hex_code']; ?>"
                                             title="<?php echo htmlspecialchars($color['name']); ?>"
                                             onclick="selectColor(this)"></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Click to select color</div>
                        </div>
                    </div>

                    <div class="specification-card">
                        <h4 class="mb-3">Additional Information</h4>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="Any special requirements, finishing preferences, or other notes..."></textarea>
                        </div>
                        
                        <div class="price-display">
                            <h5>Estimated Price</h5>
                            <p class="price-note">We will review your order and provide final pricing within 24 hours. You'll receive a notification once pricing is available.</p>
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
        
        function updateMaterialOptions() {
            const printType = document.querySelector('input[name="print_type"]:checked').value;
            const materialSelect = document.getElementById('material_id');
            
            // Clear existing options
            materialSelect.innerHTML = '<option value="">Select Material</option>';
            
            <?php foreach ($materials as $material): ?>
                const material<?php echo $material['id']; ?> = {
                    id: <?php echo $material['id']; ?>,
                    name: '<?php echo addslashes($material['name']); ?>',
                    description: '<?php echo addslashes($material['description']); ?>',
                    isResin: <?php echo (stripos($material['name'], 'resin') !== false) ? 'true' : 'false'; ?>
                };
                
                if (printType === 'SLA' && material<?php echo $material['id']; ?>.isResin) {
                    const option = document.createElement('option');
                    option.value = material<?php echo $material['id']; ?>.id;
                    option.textContent = material<?php echo $material['id']; ?>.name + ' (' + material<?php echo $material['id']; ?>.description + ')';
                    option.selected = true;
                    materialSelect.appendChild(option);
                    materialSelect.disabled = true;
                } else if (printType === 'FDM' && !material<?php echo $material['id']; ?>.isResin) {
                    const option = document.createElement('option');
                    option.value = material<?php echo $material['id']; ?>.id;
                    option.textContent = material<?php echo $material['id']; ?>.name + ' (' + material<?php echo $material['id']; ?>.description + ')';
                    if (material<?php echo $material['id']; ?>.name.toLowerCase().includes('pla')) {
                        option.selected = true;
                    }
                    materialSelect.appendChild(option);
                    materialSelect.disabled = false;
                }
            <?php endforeach; ?>
        }
        
        // Initialize material options on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMaterialOptions();
        });
    </script>
</body>
</html>