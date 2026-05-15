<?php
require_once 'includes/config.php';

$pageTitle = 'Home - Batu Akik Berkualitas';
$currentPage = 'home';

include 'includes/header.php';

// Fetch featured products
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 'active' AND p.featured = 1 
                         ORDER BY p.created_at DESC 
                         LIMIT 8");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured_products = [];
}

// Fetch categories
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order LIMIT 7");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Fetch latest products
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 'active' 
                         ORDER BY p.created_at DESC 
                         LIMIT 8");
    $latest_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latest_products = [];
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Core Stone Indonesia<br>Batu Akik Berkualitas</h1>
            <p>Temukan koleksi batu akik alami terbaik dari seluruh Nusantara. Kualitas terjamin dengan harga terbaik.</p>
            <div class="hero-buttons">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Belanja Sekarang</a>
                <a href="<?php echo SITE_URL; ?>/categories.php" class="btn btn-secondary">Lihat Kategori</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?php echo SITE_URL; ?>/assets/images/hero-batu-akik.png" alt="Batu Akik Indonesia" style="max-width: 100%; height: auto;">
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-title">
            <h2>Kategori Populer</h2>
            <p>Pilih kategori batu akik favorit Anda</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $category['slug']; ?>" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                <span>Lihat Produk</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<?php if (!empty($featured_products)): ?>
<section class="products-section">
    <div class="container">
        <div class="section-title">
            <h2>Produk Unggulan</h2>
            <p>Pilihan batu akik terbaik untuk Anda</p>
        </div>
        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php 
                    $images = json_decode($product['images'], true);
                    $main_image = !empty($images[0]) ? $images[0] : 'default-product.jpg';
                    ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <?php if ($product['discount_price']): ?>
                        <span class="product-badge">Diskon</span>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <button class="product-action-btn" title="Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="product-action-btn" title="Quick View">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?php echo formatRupiah($product['discount_price'] ?? $product['price']); ?></span>
                        <?php if ($product['discount_price']): ?>
                            <span class="price-original"><?php echo formatRupiah($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-meta">
                        <div class="product-rating">
                            <div class="stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <span>(4.5)</span>
                        </div>
                        <span><i class="fas fa-shopping-bag"></i> <?php echo $product['sold_count']; ?> Terjual</span>
                    </div>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest Products Section -->
<section class="products-section" style="background: var(--off-white);">
    <div class="container">
        <div class="section-title">
            <h2>Produk Terbaru</h2>
            <p>Koleksi terbaru batu akik pilihan</p>
        </div>
        <div class="products-grid">
            <?php foreach ($latest_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php 
                    $images = json_decode($product['images'], true);
                    $main_image = !empty($images[0]) ? $images[0] : 'default-product.jpg';
                    ?>
                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <?php if ($product['discount_price']): ?>
                        <span class="product-badge">Diskon</span>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <button class="product-action-btn" title="Wishlist">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="product-action-btn" title="Quick View">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    <h3 class="product-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo $product['slug']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?php echo formatRupiah($product['discount_price'] ?? $product['price']); ?></span>
                        <?php if ($product['discount_price']): ?>
                            <span class="price-original"><?php echo formatRupiah($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-meta">
                        <div class="product-rating">
                            <div class="stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <span>(4.5)</span>
                        </div>
                        <span><i class="fas fa-shopping-bag"></i> <?php echo $product['sold_count']; ?> Terjual</span>
                    </div>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Lihat Semua Produk</a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-title">
            <h2>Kenapa Memilih Kami?</h2>
            <p>Keunggulan Core Stone Indonesia</p>
        </div>
        <div class="categories-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Produk Asli</h3>
                <span>Jaminan keaslian batu akik</span>
            </div>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3>Pengiriman Cepat</h3>
                <span>Proses pengiriman cepat & aman</span>
            </div>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>Harga Terbaik</h3>
                <span>Harga kompetitif & bergaransi</span>
            </div>
            <div class="category-card">
                <div class="category-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Layanan 24/7</h3>
                <span>Customer service siap membantu</span>
            </div>
        </div>
    </div>
</section>

<script>
function addToCart(productId) {
    fetch('<?php echo SITE_URL; ?>/api/cart-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produk berhasil ditambahkan ke keranjang!');
            location.reload();
        } else {
            alert(data.message || 'Gagal menambahkan produk ke keranjang');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
