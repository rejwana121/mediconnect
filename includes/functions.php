<?php
// ============================================
// MediConnect - Helper Functions
// ============================================

require_once 'config.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------
// Authentication Functions
// -----------------------------------------------

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    global $conn;
    $id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/auth/login.php");
        exit();
    }
    if ($role && $_SESSION['role'] !== $role) {
        header("Location: " . SITE_URL . "/auth/unauthorized.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        redirectToDashboard();
    }
}

function redirectToDashboard() {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: " . SITE_URL . "/admin/dashboard.php");
    } elseif ($role === 'doctor') {
        header("Location: " . SITE_URL . "/doctor/dashboard.php");
    } else {
        header("Location: " . SITE_URL . "/patient/dashboard.php");
    }
    exit();
}

// -----------------------------------------------
// Security Functions
// -----------------------------------------------

function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

// -----------------------------------------------
// Notification Functions
// -----------------------------------------------

function createNotification($conn, $user_id, $title, $message, $type = 'system') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    return $stmt->execute();
}

function getUnreadNotifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function countUnreadNotifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

function markNotificationsRead($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// -----------------------------------------------
// Appointment Functions
// -----------------------------------------------

function getAppointmentStatus($status) {
    $badges = [
        'pending'   => '<span class="badge bg-warning text-dark">Pending</span>',
        'confirmed' => '<span class="badge bg-success">Confirmed</span>',
        'completed' => '<span class="badge bg-primary">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getAvailableTimeSlots($conn, $doctor_id, $date) {
    // Get doctor's schedule
    $stmt = $conn->prepare("SELECT start_time, end_time, slot_duration FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();

    if (!$doctor) return [];

    // Get booked slots for that date
    $stmt2 = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt2->bind_param("is", $doctor_id, $date);
    $stmt2->execute();
    $booked = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $bookedTimes = array_column($booked, 'appointment_time');

    // Generate time slots
    $slots = [];
    $start = strtotime($doctor['start_time']);
    $end   = strtotime($doctor['end_time']);
    $duration = $doctor['slot_duration'] * 60;

    while ($start < $end) {
        $timeStr = date('H:i:s', $start);
        if (!in_array($timeStr, $bookedTimes)) {
            $slots[] = date('h:i A', $start);
        }
        $start += $duration;
    }

    return $slots;
}

// -----------------------------------------------
// Utility Functions
// -----------------------------------------------

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    return floor($time/86400) . ' days ago';
}

function uploadImage($file, $folder = 'profiles') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;

    $newName = uniqid() . '_' . time() . '.' . $ext;
    $uploadDir = UPLOAD_PATH . $folder . '/';

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        return $folder . '/' . $newName;
    }
    return false;
}

function setAlert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return "<div class='alert alert-{$alert['type']} alert-dismissible fade show' role='alert'>
                    {$alert['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

function formatDoctorName($name) {
    $clean = preg_replace('/^Dr\.?\s+/i', '', trim($name));
    return 'Dr. ' . $clean;
}
?>
