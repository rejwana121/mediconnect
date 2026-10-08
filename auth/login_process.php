<?php
require_once '../includes/functions.php';
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        setAlert('danger', 'Please fill in all fields.');
        header("Location: login.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['image']     = $user['profile_image'];

        // Create login notification
        createNotification($conn, $user['id'], 'Login Successful', 'You have logged in successfully.', 'system');

        setAlert('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirectToDashboard();
    } else {
        setAlert('danger', 'Invalid email or password. Please try again.');
        header("Location: login.php");
        exit();
    }
}
?>
