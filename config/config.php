<?php
// General Configuration

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('UTC');

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Site URL (update this to your domain)
define('SITE_URL', 'http://localhost/earningsllc');
define('SITE_NAME', 'EarningsLLC');

// Include database configuration
require_once __DIR__ . '/database.php';

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Helper function to generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>
