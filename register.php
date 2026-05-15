<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$pageTitle = 'Daftar Akun';
$error_message = '';
$success_message = '';

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = sanitize($_POST['phone'] ?? '');
        
        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $error_message = 'Semua field wajib diisi';
        } elseif (!validateEmail($email)) {
            $error_message = 'Format email tidak valid';
        } elseif (!validatePassword($password)) {
            $error_message = 'Password minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Password dan konfirmasi password tidak sama';
        } elseif (strlen($name) < 3) {
            $error_message = 'Nama minimal 3 karakter';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error_message = 'Email sudah terdaftar. Silakan login atau gunakan email lain.';
                } else {
                    // Hash password and insert user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'user', 'active', NOW())");
                    $stmt->execute([$name, $email, $hashed_password, $phone]);
                    
                    $success_message = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
                    
                    // Clear form
                    $_POST = [];
                }
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error_message = 'Terjadi kesalahan sistem. Silakan coba lagi nanti.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Core Stone Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #800020;
            --primary-dark: #5c0016;
            --gradient-primary: linear-gradient(135deg, #800020 0%, #c41e3a 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            background: var(--gradient-primary);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .register-header i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ffd700;
        }
        
        .register-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .register-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .register-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(128, 0, 32, 0.3);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .register-header {
                padding: 30px 20px;
            }
            
            .register-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <a href="<?php echo SITE_URL; ?>" class="back-home">
        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
    
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h1>Core Stone</h1>
            <p>Buat akun baru</p>
        </div>
        
        <div class="register-body">
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Nama Lengkap</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="Masukkan nama lengkap Anda">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="Masukkan email Anda">
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Nomor WhatsApp (Opsional)</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           placeholder="Contoh: 081234567890">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Minimal 8 karakter">
                    <small>Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Ulangi password Anda">
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
            </form>
            
            <div class="register-footer">
                <p>Sudah punya akun? <a href="login.php">Login sekarang</a></p>
            </div>
        </div>
    </div>
</body>
</html>
