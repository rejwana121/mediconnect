<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name']);
    $email   = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        if ($stmt->execute()) {
            setAlert('success', '✅ Your message has been sent! We\'ll get back to you soon.');
        } else {
            setAlert('danger', 'Failed to send message. Please try again.');
        }
    } else {
        setAlert('danger', 'Please fill in all required fields.');
    }
}

header("Location: index.php#contact");
exit();
?>
