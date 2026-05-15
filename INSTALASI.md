# Panduan Instalasi Core Stone Indonesia

## Solusi Error #1044 - Access Denied

Error ini terjadi karena Anda tidak memiliki izin untuk membuat database di hosting shared. Ikuti langkah berikut:

### Langkah 1: Buat Database Manual di cPanel

1. Login ke **cPanel** hosting Anda
2. Masuk ke menu **MySQL® Databases**
3. Di bagian "Create New Database":
   - Nama database: `corestone_db` (atau biarkan prefix otomatis, misal: `cpses_ulosrqmp5o_corestone_db`)
   - Klik **Create Database**

### Langkah 2: Buat User Database

1. Masih di halaman MySQL® Databases
2. Di bagian "Add New User":
   - Username: `corestone_user` (atau biarkan prefix otomatis)
   - Password: Buat password yang kuat
   - Klik **Create User**

### Langkah 3: Hubungkan User ke Database

1. Di bagian "Add User To Database":
   - Pilih user yang baru dibuat
   - Pilih database yang baru dibuat
   - Klik **Add**
   - Centang **ALL PRIVILEGES**
   - Klik **Make Changes**

### Langkah 4: Update File Konfigurasi

Edit file `includes/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'cpses_ulosrqmp5o_corestone_db'); // Sesuaikan dengan nama database lengkap
define('DB_USER', 'cpses_ulosrqmp5o_corestone_user'); // Sesuaikan dengan username lengkap
define('DB_PASS', 'password_anda_dibuat_di_langkah_2'); // Isi password dari Langkah 2

// Site Configuration
define('SITE_URL', 'https://domain-anda.com'); // Ganti dengan domain Anda
```

### Langkah 5: Import Database

1. Buka **phpMyAdmin** di cPanel
2. Pilih database yang sudah dibuat (`cpses_ulosrqmp5o_corestone_db`)
3. Klik tab **Import**
4. Upload file `database.sql`
5. Klik **Go**

> ⚠️ **PENTING**: File `database.sql` sudah dimodifikasi untuk TIDAK menggunakan perintah `CREATE DATABASE`. Langsung buat tabel saja.

### Langkah 6: Verifikasi Instalasi

1. Akses website Anda: `https://domain-anda.com`
2. Jika muncul error koneksi, periksa kembali:
   - Nama database (harus lengkap dengan prefix)
   - Username database (harus lengkap dengan prefix)
   - Password database
   - Host (biasanya `localhost`, tapi beberapa hosting menggunakan host khusus)

### Login Default Admin

Setelah instalasi berhasil:
- **URL Admin**: `https://domain-anda.com/admin/`
- **Email**: `admin@corestone.id`
- **Password**: `password`

> 🔒 **SEGERA UBAH PASSWORD ADMIN** setelah login pertama kali!

### Konfigurasi Tripay Payment Gateway

1. Daftar di [Tripay](https://tripay.co.id)
2. Dapatkan API Key dan Private Key dari dashboard
3. Update file `includes/config.php`:

```php
define('TRIPAY_API_KEY', 'api_key_anda');
define('TRIPAY_PRIVATE_KEY', 'private_key_anda');
define('TRIPAY_MERCHANT_CODE', 'kode_merchant_anda');
define('TRIPAY_MODE', 'sandbox'); // Gunakan 'sandbox' untuk testing, 'production' untuk live
```

4. Setup Callback URL di Tripay Dashboard:
   - URL: `https://domain-anda.com/api/tripay-callback.php`

### Troubleshooting

#### Error: Access denied for user
- Pastikan username dan password benar
- Pastikan user sudah di-link ke database dengan ALL PRIVILEGES
- Cek apakah ada prefix otomatis di cPanel

#### Error: Unknown database
- Pastikan database sudah dibuat
- Pastikan nama database di config.php sesuai (termasuk prefix)

#### Error: Can't connect to MySQL server
- Cek DB_HOST, beberapa hosting menggunakan host khusus (misal: `mysql.domainanda.com`)
- Pastikan database server aktif

### Struktur Database

Database akan membuat tabel-tabel berikut:
- `users` - Data pengguna (customer & admin)
- `categories` - Kategori produk
- `products` - Produk batu akik
- `product_images` - Gambar produk
- `orders` - Pesanan
- `order_items` - Detail pesanan
- `payments` - Pembayaran
- `settings` - Pengaturan toko

### Keamanan

1. Ganti semua password default
2. Update TRIPAY keys dengan yang asli
3. Backup database secara berkala
4. Gunakan HTTPS (SSL) untuk production

### Support WhatsApp

Untuk bantuan lebih lanjut, hubungi:
📱 WhatsApp: 0812-1493-2916

---

**Core Stone Indonesia** - Toko Batu Akik Terpercaya
