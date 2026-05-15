<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'corestone_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Core Stone Indonesia');
define('SITE_URL', 'http://localhost/corestone');
define('ADMIN_EMAIL', 'admin@corestone.id');
define('WHATSAPP_NUMBER', '6281214932916');

// Tripay Configuration (Sandbox)
define('TRIPAY_API_KEY', 'YOUR_TRIPAY_API_KEY');
define('TRIPAY_PRIVATE_KEY', 'YOUR_TRIPAY_PRIVATE_KEY');
define('TRIPAY_MERCHANT_CODE', 'T12345');
define('TRIPAY_MODE', 'sandbox'); // sandbox or production
define('TRIPAY_SANDBOX_URL', 'https://tripay.co.id/api-sandbox/');
define('TRIPAY_PRODUCTION_URL', 'https://tripay.co.id/api/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
session_start();

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper Functions
function redirect($url) {
    header("Location: " . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderCode() {
    return 'CS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
