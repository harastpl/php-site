<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/email_functions.php';

$error = '';
$success = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_otp'])) {
        $email = trim($_POST['email']);
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['reset_email'] = $email;
                $otp = generateOTP();
                
                if (storeOTP($email, $otp, 'password_reset')) {
                    if (sendOTPEmail($email, $otp, 'password_reset')) {
                        $success = 'OTP has been sent to your email address.';
                    } else {
                        $success = "OTP generated but email failed to send. Your OTP is: $otp";
                    }
                } else {
                    $error = 'Failed to generate OTP. Please try again.';
                }
                
                $step = 2;
            } else {
                $error = 'No user found with that email address';
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $otp_entered = trim($_POST['otp']);
        $email = $_SESSION['reset_email'];
        
        if (verifyOTP($email, $otp_entered, 'password_reset')) {
            $step = 3;
        } else {
            $error = 'Invalid or expired OTP';
            $step = 2;
        }
    } elseif (isset($_POST['reset_password'])) {
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $email = $_SESSION['reset_email'];
        
        if (empty($password) || empty($confirm_password)) {
            $error = 'Please enter and confirm your new password';
            $step = 3;
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
            $step = 3;
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
            $step = 3;
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            if ($stmt->execute([$hashedPassword, $email])) {
                $success = 'Your password has been reset successfully. You can now login.';
                unset($_SESSION['reset_email']);
                $step = 4;
            } else {
                $error = 'Failed to reset password. Please try again.';
                $step = 3;
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
    <title>Forgot Password | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 mt-5">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center">Forgot Password</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <button type="submit" name="send_otp" class="btn btn-primary">Send OTP</button>
                            </form>
                        <?php elseif ($step == 2): ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="otp" class="form-label">Enter OTP</label>
                                    <input type="text" class="form-control" id="otp" name="otp" maxlength="6" required>
                                    <div class="form-text">Check your email for the 6-digit OTP</div>
                                </div>
                                <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
                                <div class="text-center mt-3">
                                    <a href="forgot_password.php">Resend OTP</a>
                                </div>
                            </form>
                        <?php elseif ($step == 3): ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                            </form>
                        <?php elseif ($step == 4): ?>
                            <div class="alert alert-success">
                                Password reset successful! <a href="login.php">Login here</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>