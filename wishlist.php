<?php
/**
 * Wishlist Page - Core Stone Indonesia
 * Allows users to save favorite products
 */
require_once 'includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'wishlist.php';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle remove from wishlist
if (isset($_GET['remove']) && isset($_GET['token'])) {
    if (!verify_csrf_token($_GET['token'])) {
        $error_message = 'Token CSRF tidak valid.';
    } else {
        $product_id = (int)$_GET['remove'];
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        if ($stmt->execute()) {
            $success_message = 'Produk berhasil dihapus dari wishlist.';
        }
        $stmt->close();
    }
}

// Handle add to cart from wishlist
if (isset($_POST['add_to_cart']) && isset($_POST['token'])) {
    if (!verify_csrf_token($_POST['token'])) {
        $error_message = 'Token CSRF tidak valid.';
    } else {
        $product_id = (int)$_POST['product_id'];
        
        // Check stock
        $stmt = $conn->prepare("SELECT stock, price, discount_price FROM products WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            if ($product['stock'] > 0) {
                // Add to cart
                $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                $check_stmt->bind_param("ii", $user_id, $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update quantity
                    $cart_item = $check_result->fetch_assoc();
                    $new_quantity = $cart_item['quantity'] + 1;
                    
                    if ($new_quantity <= $product['stock']) {
                        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        $success_message = 'Jumlah produk berhasil diperbarui di keranjang.';
                    } else {
                        $error_message = 'Stok tidak mencukupi.';
                    }
                } else {
                    // Insert new cart item
                    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                    $insert_stmt->bind_param("ii", $user_id, $product_id);
                    if ($insert_stmt->execute()) {
                        $success_message = 'Produk berhasil ditambahkan ke keranjang.';
                    } else {
                        $error_message = 'Gagal menambahkan produk ke keranjang.';
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            } else {
                $error_message = 'Produk sedang habis stok.';
            }
        } else {
            $error_message = 'Produk tidak ditemukan.';
        }
        $stmt->close();
    }
}

// Get wishlist items
$query = "
    SELECT w.id as wishlist_id, p.*, 
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as main_image
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();
$stmt->close();

$page_title = 'Wishlist Saya';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Wishlist</li>
                </ol>
            </nav>
            
            <h1 class="mb-4">Wishlist Saya</h1>
            
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
            
            <?php if ($wishlist_items->num_rows > 0): ?>
                <div class="row">
                    <?php while ($item = $wishlist_items->fetch_assoc()): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card product-card h-100">
                                <div class="position-relative">
                                    <a href="product.php?id=<?= $item['id'] ?>">
                                        <img src="<?= !empty($item['main_image']) ? htmlspecialchars($item['main_image']) : 'assets/images/placeholder.jpg' ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($item['name']) ?>" 
                                             style="height: 200px; object-fit: cover;">
                                    </a>
                                    <button onclick="removeFromWishlist(<?= $item['wishlist_id'] ?>)" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title">
                                        <a href="product.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </h6>
                                    
                                    <?php if ($item['discount_price'] && $item['discount_price'] < $item['price']): ?>
                                        <div class="mb-2">
                                            <span class="text-muted text-decoration-line-through small">Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                                            <div class="text-danger fw-bold">Rp <?= number_format($item['discount_price'], 0, ',', '.') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-primary fw-bold mb-2">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2 small">
                                        <i class="fas fa-box text-muted"></i> Stok: <?= $item['stock'] ?>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <?php if ($item['stock'] > 0): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="token" value="<?= generate_csrf_token() ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="fas fa-times-circle"></i> Habis Stok
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i> Lihat Produk Lainnya
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-heart-broken text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Wishlist Anda Masih Kosong</h4>
                    <p class="text-muted">Simpan produk favorit Anda untuk memudahkan pembelian nanti.</p>
                    <a href="products.php" class="btn btn-primary mt-3">
                        <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function removeFromWishlist(wishlistId) {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini dari wishlist?')) {
        window.location.href = 'wishlist.php?remove=' + wishlistId + '&token=<?= generate_csrf_token() ?>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
