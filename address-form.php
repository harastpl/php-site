<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

// Get current address
$stmt = $pdo->prepare("SELECT address, phone FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$current_address = null;
if (!empty($user['address'])) {
    $current_address = json_decode($user['address'], true);
}

$current_phone = $user['phone'] ?? '+91';


$redirect = $_GET['redirect'] ?? 'orders.php';

$states = [
    'Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh', 'Chhattisgarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Puducherry', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Address | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h4 class="mb-0">Delivery Address</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="update-address.php?redirect=<?php echo urlencode($redirect); ?>">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($current_address['full_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($current_address['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($current_address['city'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="state" class="form-label">State *</label>
                                        <select class="form-select" id="state" name="state" required>
                                            <option value="">Select State</option>
                                            <?php foreach ($states as $state): ?>
                                                <option value="<?php echo $state; ?>" <?php echo (isset($current_address['state']) && $current_address['state'] == $state) ? 'selected' : ''; ?>>
                                                    <?php echo $state; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="pincode" class="form-label">Pincode *</label>
                                        <input type="text" class="form-control" id="pincode" name="pincode" 
                                               value="<?php echo htmlspecialchars($current_address['pincode'] ?? ''); ?>" 
                                               pattern="\d{6}" maxlength="6" required>
                                        <div class="form-text">6-digit pincode</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($current_phone); ?>" 
                                               pattern="\+\d{12}" required>
                                        <div class="form-text">Include country code (e.g., +91 followed by 10 digits)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo htmlspecialchars($redirect); ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Address</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d+]/g, '');
            if (value.startsWith('+')) {
                value = '+' + value.substring(1).replace(/[^\d]/g, '');
            } else {
                value = '+' + value.replace(/[^\d]/g, '');
            }

            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            e.target.value = value;
        });
        
        // Validate phone number
        document.getElementById('phone').addEventListener('blur', function(e) {
            let value = e.target.value;
            let phoneRegex = /^\+\d{12}$/;
            if (!phoneRegex.test(value)) {
                e.target.setCustomValidity('Please enter a valid phone number with country code (+91 followed by 10 digits)');
            } else {
                e.target.setCustomValidity('');
            }
        });
        
        // Validate pincode
        document.getElementById('pincode').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
        });
    </script>
</body>
</html>