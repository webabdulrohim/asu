<?php
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Keranjang Belanja';
$currentPage = 'cart';

include 'includes/header.php';

// Get cart items
$cart_items = [];
$total_amount = 0;

if (isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.discount_price, p.images, p.slug 
                              FROM cart c 
                              JOIN products p ON c.product_id = p.id 
                              WHERE c.user_id = ? AND p.status = 'active'");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cart_items as &$item) {
            $price = $item['discount_price'] ?? $item['price'];
            $item['subtotal'] = $price * $item['quantity'];
            $total_amount += $item['subtotal'];
        }
    } catch (PDOException $e) {
        $cart_items = [];
    }
} else if (isset($_SESSION['guest_cart'])) {
    try {
        foreach ($_SESSION['guest_cart'] as $product_id => $quantity) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $price = $product['discount_price'] ?? $product['price'];
                $subtotal = $price * $quantity;
                
                $cart_items[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'discount_price' => $product['discount_price'],
                    'images' => $product['images'],
                    'slug' => $product['slug'],
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
                
                $total_amount += $subtotal;
            }
        }
    } catch (PDOException $e) {
        $cart_items = [];
    }
}
?>

<div class="page-header" style="background: var(--gradient-primary); padding: 40px 0; color: white;">
    <div class="container">
        <h1>Keranjang Belanja</h1>
        <p>Review produk Anda sebelum checkout</p>
    </div>
</div>

<section class="cart-section" style="padding: 60px 0;">
    <div class="container">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart" style="text-align: center; padding: 60px 0;">
                <i class="fas fa-shopping-cart" style="font-size: 80px; color: var(--primary-light); margin-bottom: 20px;"></i>
                <h2>Keranjang Anda Kosong</h2>
                <p style="margin: 20px 0;">Mulai belanja sekarang untuk mengisi keranjang Anda</p>
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Belanja Sekarang</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $images = json_decode($item['images'], true);
                        $main_image = !empty($images[0]) ? $images[0] : 'default-product.jpg';
                        $price = $item['discount_price'] ?? $item['price'];
                        ?>
                        <div class="cart-item" style="display: flex; gap: 20px; padding: 20px; background: white; border-radius: var(--radius-lg); margin-bottom: 20px; box-shadow: var(--shadow-sm);">
                            <div class="cart-item-image" style="width: 120px; height: 120px; flex-shrink: 0;">
                                <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-md);">
                            </div>
                            <div class="cart-item-details" style="flex: 1;">
                                <h3 style="font-size: 16px; margin-bottom: 10px;">
                                    <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $item['slug']; ?>" style="color: var(--text-dark);">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                <div class="cart-item-price" style="font-size: 18px; font-weight: 700; color: var(--primary-color); margin-bottom: 15px;">
                                    <?php echo formatRupiah($price); ?>
                                </div>
                                <div class="cart-item-quantity" style="display: flex; align-items: center; gap: 10px;">
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, -1)" style="width: 30px; height: 30px; border: 1px solid var(--border-color); background: white; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span style="min-width: 40px; text-align: center;"><?php echo $item['quantity']; ?></span>
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, 1)" style="width: 30px; height: 30px; border: 1px solid var(--border-color); background: white; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button onclick="removeCartItem(<?php echo $item['id']; ?>)" style="margin-left: auto; color: var(--danger-color); background: none; border: none; cursor: pointer;">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                            <div class="cart-item-subtotal" style="text-align: right; min-width: 120px;">
                                <div style="font-size: 14px; color: var(--text-muted);">Subtotal</div>
                                <div style="font-size: 18px; font-weight: 700; color: var(--primary-color);">
                                    <?php echo formatRupiah($item['subtotal']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary" style="background: white; padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); height: fit-content; position: sticky; top: 100px;">
                    <h2 style="font-size: 20px; margin-bottom: 20px; color: var(--primary-color);">Ringkasan Pesanan</h2>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-light);">Total Produk</span>
                        <span style="font-weight: 600;"><?php echo formatRupiah($total_amount); ?></span>
                    </div>
                    
                    <div class="summary-row" style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-light);">Ongkos Kirim</span>
                        <span style="font-weight: 600; color: var(--success-color);">Gratis</span>
                    </div>
                    
                    <div class="summary-total" style="display: flex; justify-content: space-between; margin-bottom: 25px; padding-top: 15px;">
                        <span style="font-size: 18px; font-weight: 700; color: var(--text-dark);">Total Bayar</span>
                        <span style="font-size: 20px; font-weight: 700; color: var(--primary-color);"><?php echo formatRupiah($total_amount); ?></span>
                    </div>
                    
                    <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                        <i class="fas fa-credit-card"></i> Checkout
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-secondary" style="width: 100%; background: var(--off-white); color: var(--text-dark); border: 1px solid var(--border-color);">
                        <i class="fas fa-arrow-left"></i> Lanjut Belanja
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function updateCartItem(productId, change) {
    fetch('<?php echo SITE_URL; ?>/api/cart-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            change: change
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal mengupdate keranjang');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}

function removeCartItem(productId) {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')) {
        fetch('<?php echo SITE_URL; ?>/api/cart-remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Gagal menghapus produk');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan. Silakan coba lagi.');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
