<?php
require_once '../includes/functions.php';
redirectIfLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = sanitize($_POST['full_name']);
    $email            = sanitize($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role             = $_POST['role'] === 'doctor' ? 'doctor' : 'patient';
    $phone            = sanitize($_POST['phone'] ?? '');
    $gender           = sanitize($_POST['gender'] ?? '');
    $date_of_birth    = sanitize($_POST['date_of_birth'] ?? '');

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        setAlert('danger', 'Please fill in all required fields.');
        header("Location: register.php"); exit();
    }

    if ($password !== $confirm_password) {
        setAlert('danger', 'Passwords do not match.');
        header("Location: register.php"); exit();
    }

    if (strlen($password) < 6) {
        setAlert('danger', 'Password must be at least 6 characters.');
        header("Location: register.php"); exit();
    }

    // Check email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        setAlert('danger', 'This email is already registered.');
        header("Location: register.php"); exit();
    }

    $hashedPassword = hashPassword($password);
    $dob = !empty($date_of_birth) ? $date_of_birth : null;

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $full_name, $email, $hashedPassword, $role, $phone, $gender, $dob);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        if ($role === 'doctor') {
            $specialization   = sanitize($_POST['specialization'] ?? 'General Medicine');
            $qualification    = sanitize($_POST['qualification'] ?? '');
            $experience_years = intval($_POST['experience_years'] ?? 0);
            $consultation_fee = floatval($_POST['consultation_fee'] ?? 0);

            $stmt2 = $conn->prepare("INSERT INTO doctors (user_id, specialization, qualification, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("issid", $user_id, $specialization, $qualification, $experience_years, $consultation_fee);
            $stmt2->execute();

            setAlert('info', 'Registration successful! Your account is pending admin approval.');
        } else {
            $blood_group       = sanitize($_POST['blood_group'] ?? '');
            $emergency_contact = sanitize($_POST['emergency_contact'] ?? '');

            $stmt2 = $conn->prepare("INSERT INTO patients (user_id, blood_group, emergency_contact) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $user_id, $blood_group, $emergency_contact);
            $stmt2->execute();

            // Welcome notification
            createNotification($conn, $user_id, 'Welcome to MediConnect!', 'Your account has been created. You can now book appointments.', 'system');
            setAlert('success', 'Registration successful! Please login.');
        }

        header("Location: login.php"); exit();
    } else {
        setAlert('danger', 'Registration failed. Please try again.');
        header("Location: register.php"); exit();
    }
}
?>
