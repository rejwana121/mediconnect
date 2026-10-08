<?php
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - MediConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card text-center" style="max-width: 520px;">
        <div class="auth-logo mx-auto mb-4" style="background: rgba(239, 68, 68, 0.1); color: #EF4444;">
            <i class="fas fa-user-shield"></i>
        </div>
        <h2 class="auth-title mb-2">Access Restricted</h2>
        <p class="auth-subtitle mb-4">You do not have the required role permissions to view this administrative or clinical area.</p>

        <div class="d-flex flex-column gap-2 mb-3">
            <?php if(isLoggedIn()): ?>
                <a href="<?php 
                    $role = $_SESSION['role'] ?? 'patient';
                    echo SITE_URL . '/' . $role . '/dashboard.php';
                ?>" class="btn btn-primary py-3">
                    <i class="fas fa-home me-2"></i>Return to My Dashboard
                </a>
                <a href="<?= SITE_URL ?>/auth/logout.php" class="btn btn-outline-danger py-2">
                    <i class="fas fa-sign-out-alt me-2"></i>Switch Account / Sign Out
                </a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-primary py-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In to Continue
                </a>
            <?php endif; ?>
        </div>

        <small class="text-muted">MediConnect Security & Role-Based Access Control</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
