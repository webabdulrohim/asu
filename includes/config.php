<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpses_ulosrqmp5o_corestone_db'); // Sesuaikan dengan nama database di hosting
define('DB_USER', 'cpses_ulosrqmp5o'); // User database dari hosting
define('DB_PASS', 'YOUR_DATABASE_PASSWORD'); // ⚠️ PENTING: Isi dengan password database dari hosting Anda

// Site Configuration
define('SITE_NAME', 'Core Stone Indonesia');
define('SITE_URL', 'https://corestone.id'); // Ganti dengan domain Anda
define('ADMIN_EMAIL', 'admin@corestone.id');
define('WHATSAPP_NUMBER', '6281214932916');

// Tripay Configuration (Sandbox) - ⚠️ PENTING: Ganti dengan API keys Anda
define('TRIPAY_API_KEY', 'YOUR_TRIPAY_API_KEY');
define('TRIPAY_PRIVATE_KEY', 'YOUR_TRIPAY_PRIVATE_KEY');
define('TRIPAY_MERCHANT_CODE', 'T12345');
define('TRIPAY_MODE', 'sandbox'); // sandbox or production
define('TRIPAY_SANDBOX_URL', 'https://tripay.co.id/api-sandbox/');
define('TRIPAY_PRODUCTION_URL', 'https://tripay.co.id/api/');

// Security Configuration
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Enforce HTTPS in production
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on' && strpos(SITE_URL, 'https://') === 0) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Secure Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only send cookies over HTTPS
ini_set('session.use_strict_mode', 1); // Prevent session fixation
ini_set('session.cookie_samesite', 'Strict');

// Set secure session parameters before starting
if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
} else {
    session_set_cookie_params(0, '/', '', true, true);
}

session_start();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    // Log error internally but don't expose details to users
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact administrator.");
}

// CSRF Token Functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    return $_SESSION['csrf_token'];
}

// Rate Limiting for Login Attempts
function checkLoginAttempts($ip) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $attempt = $stmt->fetch();
        
        if ($attempt) {
            $time_diff = time() - strtotime($attempt['last_attempt']);
            
            if ($attempt['attempts'] >= MAX_LOGIN_ATTEMPTS && $time_diff < LOGIN_LOCKOUT_TIME) {
                return false; // Locked out
            }
            
            if ($time_diff > LOGIN_LOCKOUT_TIME) {
                // Reset attempts after lockout period
                $stmt = $pdo->prepare("UPDATE login_attempts SET attempts = 0, last_attempt = NOW() WHERE ip_address = ?");
                $stmt->execute([$ip]);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error checking login attempts: " . $e->getMessage());
        return true; // Allow login if rate limiting fails
    }
}

function recordLoginAttempt($ip, $success) {
    global $pdo;
    
    try {
        if ($success) {
            // Clear attempts on successful login
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip]);
        } else {
            // Record failed attempt
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
            $stmt->execute([$ip]);
        }
    } catch (PDOException $e) {
        error_log("Error recording login attempt: " . $e->getMessage());
    }
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
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // Minimum 8 characters, at least one uppercase, one lowercase, one number
    return strlen($password) >= 8 && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password) && preg_match('/[0-9]/', $password);
}

function generateOrderCode() {
    return 'CS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Input Validation Helper
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $ruleSet) {
        $rulesArray = explode('|', $ruleSet);
        
        foreach ($rulesArray as $rule) {
            if ($rule === 'required' && empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
                break;
            }
            
            if ($rule === 'email' && !empty($data[$field]) && !validateEmail($data[$field])) {
                $errors[$field] = 'Invalid email format';
                break;
            }
            
            if ($rule === 'numeric' && !empty($data[$field]) && !is_numeric($data[$field])) {
                $errors[$field] = ucfirst($field) . ' must be numeric';
                break;
            }
            
            if (strpos($rule, 'min:') === 0) {
                $min = (int)substr($rule, 4);
                if (!empty($data[$field]) && strlen($data[$field]) < $min) {
                    $errors[$field] = ucfirst($field) . " must be at least {$min} characters";
                    break;
                }
            }
            
            if (strpos($rule, 'max:') === 0) {
                $max = (int)substr($rule, 4);
                if (!empty($data[$field]) && strlen($data[$field]) > $max) {
                    $errors[$field] = ucfirst($field) . " must not exceed {$max} characters";
                    break;
                }
            }
        }
    }
    
    return $errors;
}
?>
