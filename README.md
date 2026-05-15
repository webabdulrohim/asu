# Core Stone Indonesia - Toko Online Batu Akik

## 📋 Deskripsi
Core Stone Indonesia adalah platform e-commerce modern untuk toko batu akik dengan desain fresh dan UI yang menarik. Website ini dibangun menggunakan PHP native dengan integrasi payment gateway Tripay.

## ✨ Fitur Utama

### Frontend (User)
- ✅ Tampilan modern dengan warna maroon gradient yang fresh
- ✅ Responsive design untuk semua device (desktop, tablet, mobile)
- ✅ Katalog produk batu akik dengan kategori
- ✅ User registration dan login
- ✅ Keranjang belanja
- ✅ Checkout dengan integrasi Tripay payment gateway
- ✅ WhatsApp chat button di pojok bawah (0812-1493-2916)
- ✅ Halaman detail produk
- ✅ Pencarian produk
- ✅ SEO Friendly dengan meta tags dan structured data

### Backend (Admin Panel)
- ✅ Dashboard dengan statistik penjualan
- ✅ Manajemen produk (CRUD)
- ✅ Manajemen kategori
- ✅ Manajemen pesanan
- ✅ Manajemen pengguna
- ✅ Laporan penjualan
- ✅ Pengaturan sistem

### Payment Gateway
- ✅ Integrasi Tripay API
- ✅ Multiple payment methods (Bank Transfer, E-Wallet, dll)
- ✅ Callback otomatis untuk update status pembayaran
- ✅ Sandbox mode untuk testing

## 🚀 Instalasi

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi / MariaDB
- Apache/Nginx Web Server
- cURL extension enabled

### Langkah Instalasi

1. **Clone atau Download Project**
   ```bash
   cd /var/www/html
   # Atau extract file ke folder corestone
   ```

2. **Konfigurasi Database**
   - Buat database baru dengan nama `corestone_db`
   - Import file `database.sql` ke database tersebut
   ```bash
   mysql -u root -p corestone_db < database.sql
   ```

3. **Konfigurasi Aplikasi**
   - Edit file `includes/config.php`
   - Sesuaikan konfigurasi database:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'corestone_db');
     define('DB_USER', 'root');
     define('DB_PASS', 'password_anda');
     ```
   
   - Atur SITE_URL sesuai domain Anda:
     ```php
     define('SITE_URL', 'http://localhost/corestone');
     ```

4. **Konfigurasi Tripay (Optional)**
   - Dapatkan API credentials dari https://tripay.co.id
   - Update konfigurasi di `includes/config.php`:
     ```php
     define('TRIPAY_API_KEY', 'your_api_key');
     define('TRIPAY_PRIVATE_KEY', 'your_private_key');
     define('TRIPAY_MERCHANT_CODE', 'your_merchant_code');
     ```

5. **Set Permissions**
   ```bash
   chmod -R 755 /var/www/html/corestone
   chmod -R 777 /var/www/html/corestone/uploads
   ```

6. **Akses Website**
   - Frontend: http://localhost/corestone
   - Admin Panel: http://localhost/corestone/admin

## 👤 Default Login

### Admin
- Email: admin@corestone.id
- Password: password

## 📁 Struktur Folder

```
corestone/
├── admin/                  # Admin panel
│   ├── index.php          # Dashboard
│   ├── products.php       # Manajemen produk
│   └── ...
├── api/                    # API endpoints
│   ├── cart-add.php
│   ├── cart-update.php
│   └── cart-remove.php
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── main.js        # Main JavaScript
│   └── images/
├── includes/
│   ├── config.php         # Configuration
│   ├── header.php         # Frontend header
│   ├── footer.php         # Frontend footer
│   ├── tripay.php         # Tripay integration
│   └── ...
├── uploads/
│   └── products/          # Product images
├── database.sql           # Database schema
├── index.php              # Homepage
├── login.php              # Login page
├── logout.php             # Logout handler
├── cart.php               # Shopping cart
└── ...
```

## 🎨 Tema & Desain

- **Warna Utama**: Maroon Gradient (#800020 - #c41e3a)
- **Font**: Poppins (Google Fonts)
- **Icons**: Font Awesome 6
- **Responsive**: Mobile-first approach

## 🔧 Customization

### Mengubah Logo
Ganti file logo di `assets/images/logo.png`

### Mengubah Warna
Edit variabel CSS di `assets/css/style.css`:
```css
:root {
    --primary-color: #800020;
    --primary-dark: #5c0016;
    --primary-light: #a31538;
}
```

### Menambah Produk Baru
1. Login ke admin panel
2. Navigasi ke menu Produk
3. Klik "Tambah Produk Baru"
4. Isi form dan upload gambar

## 📞 Kontak & Support

- WhatsApp: 0812-1493-2916
- Email: admin@corestone.id

## 📝 License

Copyright © 2024 Core Stone Indonesia. All rights reserved.

---

**Dibuat dengan ❤️ untuk pecinta batu akik Indonesia**
