<?php
// Email configuration and functions

// Email settings
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP host
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'team@volt3dge.com'); // Your email
define('SMTP_PASSWORD', 'your_app_password'); // Your app password
define('FROM_EMAIL', 'team@volt3dge.com');
define('FROM_NAME', 'Volt3dge Team');

// Generate 6-digit OTP
function generateOTP() {
    return sprintf('%06d', mt_rand(0, 999999));
}

// Send email using PHP mail function (you can replace with SMTP later)
function sendEmail($to, $subject, $message, $type = 'general', $order_id = null) {
    global $pdo;
    
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $success = mail($to, $subject, $message, $headers);
    
    // Log email
    try {
        $stmt = $pdo->prepare("INSERT INTO email_logs (to_email, subject, message, email_type, order_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$to, $subject, $message, $type, $order_id, $success ? 'sent' : 'failed']);
    } catch (PDOException $e) {
        error_log("Failed to log email: " . $e->getMessage());
    }
    
    return $success;
}

// Send OTP email
function sendOTPEmail($email, $otp, $type = 'registration') {
    $subject = $type === 'registration' ? 'Verify Your Email - Volt3dge' : 'Password Reset OTP - Volt3dge';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3d1EFF; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .otp-box { background: white; padding: 20px; text-align: center; margin: 20px 0; border: 2px solid #3d1EFF; }
            .otp-code { font-size: 32px; font-weight: bold; color: #3d1EFF; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Volt3dge: Precision in Every Layer</h1>
            </div>
            <div class="content">
                <h2>' . ($type === 'registration' ? 'Welcome to Volt3dge!' : 'Password Reset Request') . '</h2>
                <p>' . ($type === 'registration' ? 'Thank you for registering with us. Please verify your email address using the OTP below:' : 'You have requested to reset your password. Use the OTP below to proceed:') . '</p>
                
                <div class="otp-box">
                    <p>Your OTP Code:</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p><small>This OTP will expire in 10 minutes</small></p>
                </div>
                
                <p>If you did not request this, please ignore this email.</p>
            </div>
            <div class="footer">
                <p>Best regards,<br>Volt3dge Team<br>team@volt3dge.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($email, $subject, $message, 'otp');
}

// Send order confirmation email
function sendOrderConfirmationEmail($email, $order_id, $order_details) {
    global $pdo;
    
    // Get user address and phone
    $stmt = $pdo->prepare("SELECT u.address, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $user_data = $stmt->fetch();
    
    $address_html = '';
    if (!empty($user_data['address'])) {
        $address = json_decode($user_data['address'], true);
        if ($address) {
            $address_html = '
                <div class="order-box">
                    <h3>Delivery Address</h3>
                    <p><strong>Name:</strong> ' . htmlspecialchars($address['full_name']) . '</p>
                    <p><strong>Address:</strong> ' . htmlspecialchars($address['address']) . '</p>
                    <p><strong>City:</strong> ' . htmlspecialchars($address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode']) . '</p>
                    <p><strong>Phone:</strong> ' . htmlspecialchars($address['phone']) . '</p>
                </div>';
        }
    }
    
    $subject = 'Order Confirmation #' . $order_id . ' - Volt3dge';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #3d1EFF; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .order-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #3d1EFF; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Order Confirmation</h1>
            </div>
            <div class="content">
                <h2>Thank you for your order!</h2>
                <p>Your order has been successfully placed and is being processed.</p>
                
                <div class="order-box">
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #' . $order_id . '</p>
                    <p><strong>Status:</strong> ' . ucfirst($order_details['status']) . '</p>
                    <p><strong>Total:</strong> ' . ($order_details['final_total'] > 0 ? '₹' . number_format($order_details['final_total'], 2) : 'To be updated soon') . '</p>
                    <p><strong>Order Date:</strong> ' . date('M j, Y g:i A') . '</p>
                </div>
                
                ' . $address_html . '
                
                <p>You can track your order status by logging into your account.</p>
            </div>
            <div class="footer">
                <p>Best regards,<br>Volt3dge Team<br>team@volt3dge.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send to customer
    sendEmail($email, $subject, $message, 'order_confirmation', $order_id);
    
    // Send to admin
    sendEmail(ADMIN_EMAIL, $subject, $message, 'order_confirmation', $order_id);
}

// Send payment confirmation email
function sendPaymentConfirmationEmail($email, $order_id, $amount) {
    global $pdo;
    
    // Get user address and phone
    $stmt = $pdo->prepare("SELECT u.address, u.phone FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $user_data = $stmt->fetch();
    
    $address_html = '';
    if (!empty($user_data['address'])) {
        $address = json_decode($user_data['address'], true);
        if ($address) {
            $address_html = '
                <div class="payment-box">
                    <h3>Delivery Address</h3>
                    <p><strong>Name:</strong> ' . htmlspecialchars($address['full_name']) . '</p>
                    <p><strong>Address:</strong> ' . htmlspecialchars($address['address']) . '</p>
                    <p><strong>City:</strong> ' . htmlspecialchars($address['city'] . ', ' . $address['state'] . ' - ' . $address['pincode']) . '</p>
                    <p><strong>Phone:</strong> ' . htmlspecialchars($address['phone']) . '</p>
                </div>';
        }
    }
    
    $subject = 'Payment Confirmed #' . $order_id . ' - Volt3dge';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .payment-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #28a745; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Payment Confirmed!</h1>
            </div>
            <div class="content">
                <h2>Your payment has been successfully processed</h2>
                
                <div class="payment-box">
                    <h3>Payment Details</h3>
                    <p><strong>Order ID:</strong> #' . $order_id . '</p>
                    <p><strong>Amount Paid:</strong> ₹' . number_format($amount, 2) . '</p>
                    <p><strong>Payment Date:</strong> ' . date('M j, Y g:i A') . '</p>
                    <p><strong>Status:</strong> Paid</p>
                </div>
                
                ' . $address_html . '
                
                <p>Your order is now being processed and will be shipped soon.</p>
            </div>
            <div class="footer">
                <p>Best regards,<br>Volt3dge Team<br>team@volt3dge.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send to customer
    sendEmail($email, $subject, $message, 'payment_confirmation', $order_id);
    
    // Send to admin
    sendEmail(ADMIN_EMAIL, $subject, $message, 'payment_confirmation', $order_id);
}

// Send price update email
function sendPriceUpdateEmail($email, $order_id, $new_price) {
    $subject = 'Price Updated for Order #' . $order_id . ' - Volt3dge';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1E96FF; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .price-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #1E96FF; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Price Update</h1>
            </div>
            <div class="content">
                <h2>Your custom order price has been updated</h2>
                
                <div class="price-box">
                    <h3>Order Details</h3>
                    <p><strong>Order ID:</strong> #' . $order_id . '</p>
                    <p><strong>Updated Price:</strong> ₹' . number_format($new_price, 2) . '</p>
                    <p><strong>Status:</strong> Ready for Payment</p>
                </div>
                
                <p>You can now proceed to payment by logging into your account and visiting the "My Orders" section.</p>
            </div>
            <div class="footer">
                <p>Best regards,<br>Volt3dge Team<br>team@volt3dge.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send to customer
    sendEmail($email, $subject, $message, 'price_update', $order_id);
    
    // Send to admin
    sendEmail(ADMIN_EMAIL, $subject, $message, 'price_update', $order_id);
}

// Send delivery update email
function sendDeliveryUpdateEmail($email, $order_id, $delivery_partner, $tracking_id) {
    $subject = 'Delivery Update for Order #' . $order_id . ' - Volt3dge';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #17a2b8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .delivery-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #17a2b8; }
            .footer { text-align: center; padding: 20px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Delivery Update</h1>
            </div>
            <div class="content">
                <h2>Your order is on its way!</h2>
                
                <div class="delivery-box">
                    <h3>Delivery Details</h3>
                    <p><strong>Order ID:</strong> #' . $order_id . '</p>
                    <p><strong>Delivery Partner:</strong> ' . htmlspecialchars($delivery_partner) . '</p>
                    <p><strong>Tracking ID:</strong> ' . htmlspecialchars($tracking_id) . '</p>
                </div>
                
                <p>You can track your package using the tracking ID provided above.</p>
            </div>
            <div class="footer">
                <p>Best regards,<br>Volt3dge Team<br>team@volt3dge.com</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Send to customer
    sendEmail($email, $subject, $message, 'delivery_update', $order_id);
    
    // Send to admin
    sendEmail(ADMIN_EMAIL, $subject, $message, 'delivery_update', $order_id);
}

// Store OTP in database
function storeOTP($email, $otp, $type) {
    global $pdo;
    
    // Delete any existing OTPs for this email and type
    $stmt = $pdo->prepare("DELETE FROM otp_verifications WHERE email = ? AND type = ?");
    $stmt->execute([$email, $type]);
    
    // Store new OTP (expires in 10 minutes)
    $expires_at = date('Y-m-d H:i:s', time() + 600);
    $stmt = $pdo->prepare("INSERT INTO otp_verifications (email, otp, type, expires_at) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$email, $otp, $type, $expires_at]);
}

// Verify OTP
function verifyOTP($email, $otp, $type) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id FROM otp_verifications 
        WHERE email = ? AND otp = ? AND type = ? 
        AND expires_at > NOW() AND is_used = 0
    ");
    $stmt->execute([$email, $otp, $type]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Mark OTP as used
        $stmt = $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE id = ?");
        $stmt->execute([$result['id']]);
        return true;
    }
    
    return false;
}

// Clean expired OTPs (call this periodically)
function cleanExpiredOTPs() {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM otp_verifications WHERE expires_at < NOW()");
    return $stmt->execute();
}
?>