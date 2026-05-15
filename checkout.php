<?php
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Checkout';
$currentPage = 'checkout';

include '../includes/header.php';

// Get cart items
$cart_items = [];
$total_amount = 0;

try {
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.images, p.slug, p.weight 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? AND p.status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    foreach ($cart_items as &$item) {
        $price = $item['discount_price'] ?? $item['price'];
        $item['subtotal'] = $price * $item['quantity'];
        $total_amount += $item['subtotal'];
    }
} catch (PDOException $e) {
    error_log("Error getting cart items: " . $e->getMessage());
    $cart_items = [];
}

// If cart is empty, redirect to products
if (empty($cart_items)) {
    redirect(SITE_URL . '/products.php');
}

// Get user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

$error_message = '';
$success_message = '';

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } else {
        $customer_name = sanitize($_POST['customer_name'] ?? '');
        $customer_email = sanitize($_POST['customer_email'] ?? '');
        $customer_phone = sanitize($_POST['customer_phone'] ?? '');
        $shipping_address = sanitize($_POST['shipping_address'] ?? '');
        $shipping_city = sanitize($_POST['shipping_city'] ?? '');
        $shipping_province = sanitize($_POST['shipping_province'] ?? '');
        $shipping_postal_code = sanitize($_POST['shipping_postal_code'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        $payment_method = sanitize($_POST['payment_method'] ?? 'bank_transfer');
        
        // Validation
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || 
            empty($shipping_address) || empty($shipping_city) || empty($shipping_province)) {
            $error_message = 'Semua field wajib diisi';
        } elseif (!validateEmail($customer_email)) {
            $error_message = 'Format email tidak valid';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate order code
                $order_code = generateOrderCode();
                $shipping_cost = 0; // Free shipping for now
                $grand_total = $total_amount + $shipping_cost;
                
                // Insert order
                $stmt = $pdo->prepare("INSERT INTO orders 
                    (order_code, user_id, total_amount, shipping_cost, grand_total, status, payment_method, 
                     payment_status, customer_name, customer_email, customer_phone, 
                     shipping_address, shipping_city, shipping_province, shipping_postal_code, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', ?, 'unpaid', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $order_code, $_SESSION['user_id'], $total_amount, $shipping_cost, $grand_total,
                    $payment_method, $customer_name, $customer_email, $customer_phone,
                    $shipping_address, $shipping_city, $shipping_province, $shipping_postal_code, $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Insert order items
                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare("INSERT INTO order_items 
                        (order_id, product_id, product_name, quantity, price, subtotal) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $order_id, $item['product_id'], $item['name'], 
                        $item['quantity'], $item['discount_price'] ?? $item['price'], 
                        $item['subtotal']
                    ]);
                    
                    // Update product stock
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                    
                    // Update sold count
                    $stmt = $pdo->prepare("UPDATE products SET sold_count = sold_count + ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Clear cart
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Commit transaction
                $pdo->commit();
                
                // Initialize Tripay
                require_once '../includes/tripay.php';
                $tripay = new Tripay();
                
                // Create Tripay payment
                $tripay_response = $tripay->createTransaction([
                    'method' => $payment_method,
                    'merchant_ref' => $order_code,
                    'amount' => $grand_total,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'customer_phone' => $customer_phone,
                    'order_items' => array_map(function($item) {
                        return [
                            'sku' => 'PROD-' . $item['product_id'],
                            'name' => $item['name'],
                            'price' => $item['discount_price'] ?? $item['price'],
                            'quantity' => $item['quantity']
                        ];
                    }, $cart_items),
                    'return_url' => SITE_URL . '/checkout-success.php?order=' . $order_code,
                    'signed_url' => SITE_URL . '/tripay-callback.php'
                ]);
                
                if ($tripay_response && isset($tripay_response['reference'])) {
                    // Update order with Tripay reference
                    $stmt = $pdo->prepare("UPDATE orders SET tripay_reference = ?, tripay_merchant_code = ? WHERE id = ?");
                    $stmt->execute([$tripay_response['reference'], TRIPAY_MERCHANT_CODE, $order_id]);
                    
                    // Redirect to Tripay payment page
                    if (isset($tripay_response['pay_url'])) {
                        redirect($tripay_response['pay_url']);
                    }
                }
                
                // Fallback: redirect to success page
                redirect(SITE_URL . '/checkout-success.php?order=' . $order_code);
                
            } catch (PDOException $e) {
                // Rollback on error
                $pdo->rollBack();
                error_log("Checkout error: " . $e->getMessage());
                $error_message = 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
            } catch (Exception $e) {
                // Rollback on error
                $pdo->rollBack();
                error_log("Tripay error: " . $e->getMessage());
                $error_message = 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.';
            }
        }
    }
}
?>

<div class="page-header" style="background: var(--gradient-primary); padding: 40px 0; color: white;">
    <div class="container">
        <h1>Checkout</h1>
        <p>Lengkapi data pengiriman Anda</p>
    </div>
</div>

<section class="checkout-section" style="padding: 60px 0;">
    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
                <!-- Checkout Form -->
                <div>
                    <div style="background: white; padding: 25px; border-radius: var(--radius-lg); margin-bottom: 20px; box-shadow: var(--shadow-sm);">
                        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">Informasi Pengiriman</h2>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Nama Lengkap *</label>
                                <input type="text" name="customer_name" required 
                                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Email *</label>
                                <input type="email" name="customer_email" required 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Nomor WhatsApp *</label>
                            <input type="tel" name="customer_phone" required 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="Contoh: 081234567890"
                                   style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Alamat Lengkap *</label>
                            <textarea name="shipping_address" required rows="3" 
                                      value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                      style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Kota/Kabupaten *</label>
                                <input type="text" name="shipping_city" required 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Provinsi *</label>
                                <input type="text" name="shipping_province" required 
                                       value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>"
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                            </div>
                            
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Kode Pos</label>
                                <input type="text" name="shipping_postal_code" 
                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>"
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Catatan Pesanan (Opsional)</label>
                            <textarea name="notes" rows="2" 
                                      style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;"></textarea>
                        </div>
                    </div>
                    
                    <div style="background: white; padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">Metode Pembayaran</h2>
                        
                        <div style="display: grid; gap: 15px;">
                            <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="bank_transfer" checked>
                                <div>
                                    <strong>Transfer Bank</strong>
                                    <p style="margin: 5px 0 0; color: #666; font-size: 14px;">BCA, Mandiri, BRI, BNI</p>
                                </div>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="qris">
                                <div>
                                    <strong>QRIS / E-Wallet</strong>
                                    <p style="margin: 5px 0 0; color: #666; font-size: 14px;">GoPay, OVO, Dana, ShopeePay</p>
                                </div>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="alfamart">
                                <div>
                                    <strong>Alfamart</strong>
                                    <p style="margin: 5px 0 0; color: #666; font-size: 14px;">Bayar di toko Alfamart</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div>
                    <div style="background: white; padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); position: sticky; top: 100px;">
                        <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">Ringkasan Pesanan</h2>
                        
                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                            <?php foreach ($cart_items as $item): ?>
                                <?php 
                                $images = json_decode($item['images'], true);
                                $main_image = !empty($images[0]) ? $images[0] : 'default-product.jpg';
                                $price = $item['discount_price'] ?? $item['price'];
                                ?>
                                <div style="display: flex; gap: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    <div style="flex: 1;">
                                        <h4 style="font-size: 14px; margin-bottom: 5px;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <p style="font-size: 12px; color: #666;"><?php echo $item['quantity']; ?> x <?php echo formatRupiah($price); ?></p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatRupiah($item['subtotal']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="border-top: 2px solid #e0e0e0; padding-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: #666;">Subtotal</span>
                                <span style="font-weight: 600;"><?php echo formatRupiah($total_amount); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: #666;">Ongkos Kirim</span>
                                <span style="font-weight: 600; color: var(--success-color);">Gratis</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-top: 15px; border-top: 2px solid #e0e0e0;">
                                <span style="font-size: 18px; font-weight: 700;">Total Bayar</span>
                                <span style="font-size: 20px; font-weight: 700; color: var(--primary-color);"><?php echo formatRupiah($total_amount); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; background: var(--gradient-primary); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-credit-card"></i> Buat Pesanan
                        </button>
                        
                        <a href="<?php echo SITE_URL; ?>/cart.php" style="display: block; text-align: center; margin-top: 10px; color: var(--primary-color); text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
