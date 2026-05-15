# Perbaikan Keamanan Website Core Stone Indonesia

## ✅ Perbaikan yang Telah Dilakukan

### 1. **Konfigurasi Keamanan (includes/config.php)**

#### a. Password Database
- ⚠️ **PENTING**: Ganti `YOUR_DATABASE_PASSWORD` dengan password database Anda
- Jangan biarkan password kosong di production

#### b. API Keys Tripay
- ⚠️ **PENTING**: Ganti `YOUR_TRIPAY_API_KEY` dan `YOUR_TRIPAY_PRIVATE_KEY` dengan API keys Anda
- Dapatkan dari dashboard Tripay: https://tripay.co.id

#### c. Enforce HTTPS
```php
// Otomatis redirect HTTP ke HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

#### d. Session Security
- `session.cookie_httponly = 1` - Mencegah akses JavaScript ke cookie
- `session.cookie_secure = 1` - Cookie hanya dikirim via HTTPS
- `session.use_strict_mode = 1` - Mencegah session fixation
- `session.cookie_samesite = 'Strict'` - Mencegah CSRF
- Regenerasi session ID setiap 30 menit
- Session timeout setelah 1 jam tidak aktif

### 2. **Proteksi CSRF (Cross-Site Request Forgery)**

#### Fungsi Baru:
```php
generateCSRFToken()   // Generate token unik per session
verifyCSRFToken($token) // Verifikasi token pada form submit
regenerateCSRFToken() // Regenerate token untuk keamanan ekstra
```

#### Implementasi:
- Semua form sekarang memiliki hidden field `csrf_token`
- Token diverifikasi sebelum memproses data
- Token berbeda untuk setiap session

### 3. **Rate Limiting Login**

#### Tabel Baru: `login_attempts`
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP,
    UNIQUE KEY unique_ip (ip_address)
);
```

#### Fungsi:
```php
checkLoginAttempts($ip)     // Cek apakah IP diblokir
recordLoginAttempt($ip, $success) // Catat attempt login
```

#### Kebijakan:
- Maksimal 5 percobaan login gagal
- Lockout selama 15 menit setelah mencapai batas
- Reset otomatis setelah periode lockout

### 4. **Validasi Input yang Lebih Ketat**

#### Fungsi Validasi Baru:
```php
validateEmail($email)      // Validasi format email
validatePassword($password) // Minimal 8 karakter, huruf besar, kecil, angka
validateInput($data, $rules) // Framework-style validation
sanitize($data)            // Enhanced dengan ENT_QUOTES
```

#### Password Requirements:
- Minimal 8 karakter
- Harus ada huruf uppercase (A-Z)
- Harus ada huruf lowercase (a-z)
- Harus ada angka (0-9)

### 5. **Error Handling yang Aman**

#### Sebelum:
```php
die("Database connection failed: " . $e->getMessage());
```

#### Sesudah:
```php
error_log("Database connection failed: " . $e->getMessage());
die("Database connection failed. Please contact administrator.");
```

- Error detail dicatat di server log
- User hanya melihat pesan generik
- Mencegah informasi sensitif bocor

### 6. **File-file Baru yang Dibuat**

#### a. `register.php` - Halaman Pendaftaran
- Form registrasi lengkap dengan validasi
- Proteksi CSRF
- Password strength validation
- Email uniqueness check
- Auto-hash password dengan `password_hash()`

#### b. `checkout.php` - Halaman Checkout
- Form checkout lengkap
- Integrasi dengan Tripay payment gateway
- Transaction support (rollback on error)
- Stock management otomatis
- Order tracking

### 7. **Perbaikan Guest Cart**

#### Di `login.php`:
```php
// Transfer guest cart ke user cart saat login
if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
    foreach ($_SESSION['guest_cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("INSERT INTO cart ... ON DUPLICATE KEY UPDATE...");
        $stmt->execute([...]);
    }
    unset($_SESSION['guest_cart']);
}
```

## 📋 Checklist Deployment

### Sebelum Deploy ke Production:

- [ ] Ganti `DB_PASS` di `includes/config.php`
- [ ] Ganti `TRIPAY_API_KEY` di `includes/config.php`
- [ ] Ganti `TRIPAY_PRIVATE_KEY` di `includes/config.php`
- [ ] Import `database.sql` (termasuk tabel `login_attempts`)
- [ ] Aktifkan SSL/HTTPS di hosting
- [ ] Test semua form (login, register, checkout)
- [ ] Test rate limiting login
- [ ] Test CSRF protection
- [ ] Buat folder `uploads/products/` dengan permission 755
- [ ] Set `error_reporting(0)` di production (optional)

### Konfigurasi Hosting Rekomendasi:

```apache
# .htaccess untuk keamanan tambahan
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Disable directory browsing
Options -Indexes

# Protect sensitive files
<FilesMatch "(config\.php|\.sql|\.log)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## 🔒 Best Practices Tambahan

1. **Backup Database Rutin** - Setup automatic backup harian
2. **Update Berkala** - Update PHP version dan dependencies
3. **Monitor Logs** - Cek error.log dan access.log secara rutin
4. **Strong Password Admin** - Gunakan password yang sangat kuat untuk admin
5. **Two-Factor Authentication** - Pertimbangkan 2FA untuk admin
6. **Regular Security Audit** - Scan vulnerability secara berkala

## 📞 Support

Jika ada masalah atau pertanyaan tentang implementasi keamanan ini, silakan hubungi developer atau refer ke dokumentasi:

- PHP Security Best Practices: https://www.php.net/manual/en/security.php
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Tripay Documentation: https://tripay.co.id/developer
