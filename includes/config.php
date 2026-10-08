<?php
// ============================================
// MediConnect - Database Configuration
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mediconnect_db');

define('SITE_NAME', 'MediConnect');

// Auto-detect SITE_URL for both XAMPP and VS Code built-in server
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/mediconnect') !== false || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/mediconnect') !== false) {
    define('SITE_URL', $protocol . $host . '/mediconnect');
} else {
    define('SITE_URL', $protocol . $host);
}

define('UPLOAD_PATH', __DIR__ . '/../assets/images/');

// Create Database Connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

$conn = getDBConnection();
?>
