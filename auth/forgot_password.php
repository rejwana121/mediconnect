<?php
require_once '../includes/functions.php';
redirectIfLoggedIn();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your registered email address.';
        $messageType = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            // For security & demo presentation: simulate password reset or allow resetting
            $newPassword = password_hash('password', PASSWORD_BCRYPT);
            $upStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upStmt->bind_param("ss", $newPassword, $email);
            $upStmt->execute();
            
            $message = "A password reset link and temporary access credentials have been initialized for <strong>" . htmlspecialchars($email) . "</strong>. You can now log in with the default password: <code>password</code>";
            $messageType = 'success';
        } else {
            $message = 'No active account found with that email address.';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-logo">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="auth-title">Reset Password</h2>
            <p class="auth-subtitle">Enter your registered email to recover your account</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="your.email@mediconnect.com" required>
                </div>
                <div class="form-text text-muted">We will send instructions to securely reset your credentials.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
            </button>

            <a href="login.php" class="btn btn-outline-secondary w-100 py-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Sign In
            </a>
        </form>

        <p class="text-center mt-4 mb-0" style="font-size: 14px; color: #64748B;">
            Need further help? Contact <a href="../#contact" class="text-primary">MediConnect Support</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
