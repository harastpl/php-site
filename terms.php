<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions | <?php echo defined('SITE_NAME') ? SITE_NAME : '3D Print Shop'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="mb-4">Terms and Conditions</h1>
                
                <div class="card">
                    <div class="card-body">
                        <h3>1. Acceptance of Terms</h3>
                        <p>By accessing and using Volt3dge services, you accept and agree to be bound by the terms and provision of this agreement.</p>
                        
                        <h3>2. 3D Printing Services</h3>
                        <p>We provide custom 3D printing services based on your uploaded designs. All designs must be in supported formats (STL, 3MF, OBJ, STP, STEP).</p>
                        
                        <h3>3. Order Process</h3>
                        <ul>
                            <li>Upload your design file with specifications</li>
                            <li>We will review and provide final pricing</li>
                            <li>Payment is required before production begins</li>
                            <li>Production time varies based on complexity</li>
                        </ul>
                        
                        <h3>4. Pricing and Payment</h3>
                        <p>Final pricing is determined after reviewing your design. Estimated prices are provided for reference only. Payment is processed through PhonePe gateway.</p>
                        
                        <h3>5. Quality and Materials</h3>
                        <p>We use high-quality materials including PLA, ABS, PETG, and specialty filaments. Print quality depends on design complexity and chosen specifications.</p>
                        
                        <h3>6. Delivery</h3>
                        <p>Products are delivered to the address provided during checkout. Delivery times may vary based on location and order complexity.</p>
                        
                        <h3>7. Returns and Refunds</h3>
                        <p>Returns are accepted for manufacturing defects only. Custom prints cannot be returned unless there is a quality issue on our end.</p>
                        
                        <h3>8. Intellectual Property</h3>
                        <p>You retain ownership of your designs. We do not store or use your designs for any purpose other than fulfilling your order.</p>
                        
                        <h3>9. Limitation of Liability</h3>
                        <p>Our liability is limited to the cost of the product. We are not responsible for any indirect or consequential damages.</p>
                        
                        <h3>10. Contact Information</h3>
                        <p>For any questions regarding these terms, please contact us at:</p>
                        <p><strong>Email:</strong> jayant@volt3dge.com<br>
                        <strong>Website:</strong> www.volt3dge.com</p>
                        
                        <div class="mt-4">
                            <p><small>Last updated: <?php echo date('F j, Y'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>