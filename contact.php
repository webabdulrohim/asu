<?php
/**
 * Contact Page - Core Stone Indonesia
 * Contact form and customer service information
 */
require_once 'includes/config.php';

$success_message = '';
$error_message = '';
$name_error = '';
$email_error = '';
$message_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!verify_csrf_token($_POST['token'])) {
        $error_message = 'Token CSRF tidak valid.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Validation
        if (empty($name)) {
            $name_error = 'Nama harus diisi.';
        }
        
        if (empty($email)) {
            $email_error = 'Email harus diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_error = 'Format email tidak valid.';
        }
        
        if (empty($message)) {
            $message_error = 'Pesan harus diisi.';
        } elseif (strlen($message) < 10) {
            $message_error = 'Pesan minimal 10 karakter.';
        }
        
        if (empty($name_error) && empty($email_error) && empty($message_error)) {
            // Insert to database
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $success_message = 'Pesan Anda telah terkirim. Kami akan segera menghubungi Anda.';
                // Clear form data
                $name = $email = $phone = $subject = $message = '';
            } else {
                $error_message = 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }
}

// Get contact settings
$settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('whatsapp_number', 'site_name', 'site_description')";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$page_title = 'Hubungi Kami';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Hubungi Kami</li>
                </ol>
            </nav>
            
            <h1 class="mb-4">Hubungi Kami</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Contact Information -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h3 class="card-title mb-4">Informasi Kontak</h3>
                    
                    <div class="contact-info">
                        <div class="d-flex align-items-start mb-4">
                            <div class="me-3">
                                <i class="fas fa-map-marker-alt text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Alamat</h6>
                                <p class="text-muted mb-0">Jl. Batu Akik No. 123, Jakarta, Indonesia</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="me-3">
                                <i class="fab fa-whatsapp text-success fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">WhatsApp</h6>
                                <p class="text-muted mb-0">
                                    <a href="https://wa.me/<?= htmlspecialchars($settings['whatsapp_number'] ?? '6281214932916') ?>" class="text-decoration-none">
                                        +<?= htmlspecialchars($settings['whatsapp_number'] ?? '6281214932916') ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="me-3">
                                <i class="fas fa-envelope text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Email</h6>
                                <p class="text-muted mb-0">
                                    <a href="mailto:info@corestone.id" class="text-decoration-none">info@corestone.id</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-4">
                            <div class="me-3">
                                <i class="fas fa-clock text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Jam Operasional</h6>
                                <p class="text-muted mb-0">Senin - Jumat: 08:00 - 17:00 WIB<br>Sabtu: 08:00 - 15:00 WIB<br>Minggu & Hari Libur: Tutup</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Ikuti Kami</h5>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-primary me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-danger me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="btn btn-outline-info me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-success"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Kirim Pesan</h3>
                    
                    <form method="POST" action="" novalidate>
                        <input type="hidden" name="token" value="<?= generate_csrf_token() ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= !empty($name_error) ? 'is-invalid' : '' ?>" 
                                       id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                                <?php if ($name_error): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($name_error) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?= !empty($email_error) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                                <?php if ($email_error): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($email_error) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon/WA</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="08xxxxxxxxxx">
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subjek</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?= htmlspecialchars($subject ?? '') ?>" placeholder="Pertanyaan tentang produk...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Pesan <span class="text-danger">*</span></label>
                            <textarea class="form-control <?= !empty($message_error) ? 'is-invalid' : '' ?>" 
                                      id="message" name="message" rows="5" required><?= htmlspecialchars($message ?? '') ?></textarea>
                            <?php if ($message_error): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($message_error) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.26149919595!2d106.759496!3d-6.2297465!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e945e34b9d%3A0x5371bf0fdad786a2!2sJakarta!5e0!3m2!1sid!2sid!4v1234567890" 
                            width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="mb-4">Pertanyaan yang Sering Diajukan</h2>
            
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Bagaimana cara memesan?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Pilih produk yang diinginkan, klik "Tambah ke Keranjang", lalu lanjutkan ke checkout. Isi data pengiriman dan pilih metode pembayaran.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Apa saja metode pembayaran yang tersedia?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Kami menerima pembayaran melalui transfer bank (BCA, Mandiri, BRI, BNI), e-wallet (GoPay, OVO, Dana), dan minimarket (Alfamart, Indomaret).
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Berapa lama proses pengiriman?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Untuk wilayah Jabodetabek biasanya 1-2 hari kerja. Luar Jawa 3-5 hari kerja. Waktu dapat bervariasi tergantung lokasi dan ekspedisi.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Apakah batu akik asli?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Ya, semua batu akik kami adalah 100% alami dan asli. Kami menyediakan sertifikat keaslian untuk produk-produk tertentu.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            Bagaimana kebijakan retur?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Produk dapat dikembalikan dalam waktu 7 hari jika terdapat cacat produksi atau kerusakan saat pengiriman. Produk harus dalam kondisi semula dengan kemasan lengkap.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
