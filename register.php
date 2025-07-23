<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists';
            } else {
                $_SESSION['registration_data'] = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password
                ];
                
                // Send OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                
                // In a real application, you would send the OTP via email or SMS
                // For now, we'll just display it for testing purposes
                $success = "Your OTP is: $otp";
                
                $step = 2;
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $otp_entered = trim($_POST['otp']);
        
        if ($otp_entered == $_SESSION['otp']) {
            $registration_data = $_SESSION['registration_data'];
            if (register($registration_data['username'], $registration_data['email'], $registration_data['password'])) {
                $success = 'Registration successful! You can now login.';
                unset($_SESSION['registration_data']);
                unset($_SESSION['otp']);
                $step = 3;
            } else {
                $error = 'Registration failed';
            }
        } else {
            $error = 'Invalid OTP';
            $step = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto Serif', serif;
            background: linear-gradient(135deg, #1E96FF 0%, #1EC9FF 100%);
            color: #e0e0ff;
            min-height: 100vh;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: rgba(248, 249, 250, 0.9);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(167, 139, 250, 0.3);
            border: 1px solid rgba(167, 139, 250, 0.2);
        }
        .form-control {
            background: rgba(15, 15, 26, 0.8);
            border: 1px solid rgba(167, 139, 250, 0.3);
            color: #e0e0ff;
        }
        .form-control:focus {
            background: rgba(15, 15, 26, 0.9);
            border-color: #a78bfa;
            box-shadow: 0 0 10px rgba(167, 139, 250, 0.5);
            color: #e0e0ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="text-center mb-4">
                <h2 class="glow">Register</h2>
                <p class="text-muted">Create your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success && $step != 3): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary w-100 mb-3">Register</button>
                
                <div class="text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
            <?php elseif ($step == 2): ?>
            <form method="post">
                <div class="mb-3">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" required>
                </div>
                <button type="submit" name="verify_otp" class="btn btn-primary w-100 mb-3">Verify OTP</button>
                <div class="text-center">
                    <p><a href="register.php">Resend OTP</a></p>
                </div>
            </form>
            <?php elseif ($step == 3): ?>
                <div class="alert alert-success">Registration successful! You can now <a href="login.php">login</a>.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>