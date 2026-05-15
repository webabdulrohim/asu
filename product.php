<?php
session_start();
require_once 'includes/config.php';

// Get product by slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          WHERE p.slug = ? AND p.status = 'active'");
    $stmt->execute([$slug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: index.php');
        exit;
    }
    
    // Update views
    $updateStmt = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $updateStmt->execute([$product['id']]);
    
    // Get related products
    $relatedStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status = 'active' ORDER BY RAND() LIMIT 4");
    $relatedStmt->execute([$product['category_id'], $product['id']]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse images
    $images = json_decode($product['images'] ?? '[]', true);
    if (!is_array($images)) {
        $images = [];
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = $product['meta_title'] ?? $product['name'];
$pageDescription = $product['meta_description'] ?? substr($product['description'], 0, 160);
$pageKeywords = $product['meta_keywords'] ?? $product['name'];

include 'includes/header.php';
?>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
}

.product-gallery {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
}

.thumbnail-images {
    display: flex;
    gap: 10px;
    overflow-x: auto;
}

.thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.thumbnail:hover, .thumbnail.active {
    border-color: var(--primary-color);
}

.product-info {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.product-title {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}

.product-category {
    color: var(--primary-color);
    font-size: 14px;
    margin-bottom: 15px;
}

.product-price {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 20px;
}

.original-price {
    font-size: 18px;
    color: #999;
    text-decoration: line-through;
    margin-left: 10px;
}

.product-stock {
    color: #28a745;
    font-weight: 600;
    margin-bottom: 20px;
}

.product-description {
    margin: 20px 0;
    line-height: 1.8;
    color: #555;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.meta-item {
    text-align: center;
}

.meta-label {
    font-size: 12px;
    color: #666;
}

.meta-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 20px 0;
}

.quantity-input {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.qty-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 18px;
    transition: background 0.3s;
}

.qty-btn:hover {
    background: #e9ecef;
}

.qty-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 16px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-buy, .btn-cart {
    flex: 1;
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-buy {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.btn-buy:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(128, 0, 64, 0.4);
}

.btn-cart {
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-cart:hover {
    background: var(--primary-color);
    color: white;
}

.related-products {
    margin-top: 40px;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 20px;
    color: #333;
}

@media (max-width: 768px) {
    .main-image {
        height: 300px;
    }
    
    .product-title {
        font-size: 20px;
    }
    
    .product-price {
        font-size: 24px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .product-meta {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="product-detail-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" style="margin-bottom: 20px;">
        <ol class="breadcrumb" style="display: flex; gap: 8px; list-style: none; padding: 0; margin: 0;">
            <li><a href="index.php" style="color: var(--primary-color); text-decoration: none;">Home</a></li>
            <li>/</li>
            <li><a href="index.php?category=<?= htmlspecialchars($product['category_slug']) ?>" style="color: var(--primary-color); text-decoration: none;"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li>/</li>
            <li style="color: #666;"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <!-- Product Gallery -->
        <div class="product-gallery">
            <?php if (!empty($images)): ?>
                <img src="<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image" id="mainImage">
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($image) ?>', this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <img src="assets/images/placeholder.jpg" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image" onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="product-category">
                <a href="index.php?category=<?= htmlspecialchars($product['category_slug']) ?>" style="color: var(--primary-color); text-decoration: none;">
                    <i class="fas fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?>
                </a>
            </div>

            <div class="product-price">
                Rp <?= number_format($product['price'], 0, ',', '.') ?>
                <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                    <span class="original-price">Rp <?= number_format($product['discount_price'], 0, ',', '.') ?></span>
                <?php endif; ?>
            </div>

            <div class="product-stock">
                <?php if ($product['stock'] > 0): ?>
                    <i class="fas fa-check-circle"></i> Tersedia: <?= $product['stock'] ?> unit
                <?php else: ?>
                    <i class="fas fa-times-circle" style="color: #dc3545;"></i> Stok Habis
                <?php endif; ?>
            </div>

            <div class="product-meta">
                <div class="meta-item">
                    <div class="meta-label">Dilihat</div>
                    <div class="meta-value"><?= $product['views'] ?>x</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Terjual</div>
                    <div class="meta-value"><?= $product['sold_count'] ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Berat</div>
                    <div class="meta-value"><?= $product['weight'] ?> gram</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">SKU</div>
                    <div class="meta-value"><?= $product['sku'] ?? '-' ?></div>
                </div>
            </div>

            <div class="product-description">
                <h3>Deskripsi Produk</h3>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <?php if ($product['stock'] > 0): ?>
                <div class="quantity-selector">
                    <span style="font-weight: 600;">Jumlah:</span>
                    <div class="quantity-input">
                        <button class="qty-btn" onclick="decreaseQty()">-</button>
                        <input type="number" class="qty-input" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>">
                        <button class="qty-btn" onclick="increaseQty(<?= $product['stock'] ?>)">+</button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">
                        <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                    </button>
                    <button class="btn-buy" onclick="buyNow(<?= $product['id'] ?>)">
                        <i class="fas fa-bolt"></i> Beli Sekarang
                    </button>
                </div>
            <?php else: ?>
                <button class="btn-buy" style="width: 100%; background: #ccc; cursor: not-allowed;" disabled>
                    Stok Habis
                </button>
            <?php endif; ?>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="margin: 5px 0;"><i class="fas fa-truck" style="color: var(--primary-color);"></i> Gratis ongkir untuk pembelian tertentu</p>
                <p style="margin: 5px 0;"><i class="fas fa-shield-alt" style="color: var(--primary-color);"></i> Jaminan uang kembali 7 hari</p>
                <p style="margin: 5px 0;"><i class="fas fa-certificate" style="color: var(--primary-color);"></i> Produk asli dan berkualitas</p>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="related-products">
            <h2 class="section-title">Produk Terkait</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                <?php foreach ($relatedProducts as $related): ?>
                    <?php
                    $relatedImages = json_decode($related['images'] ?? '[]', true);
                    $relatedImage = !empty($relatedImages) ? $relatedImages[0] : 'assets/images/placeholder.jpg';
                    ?>
                    <div class="product-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                        <a href="product.php?slug=<?= htmlspecialchars($related['slug']) ?>" style="text-decoration: none; color: inherit;">
                            <img src="<?= htmlspecialchars($relatedImage) ?>" alt="<?= htmlspecialchars($related['name']) ?>" style="width: 100%; height: 200px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
                            <div style="padding: 15px;">
                                <h3 style="font-size: 14px; margin: 0 0 8px 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?= htmlspecialchars($related['name']) ?></h3>
                                <div style="font-size: 16px; font-weight: 700; color: var(--primary-color);">Rp <?= number_format($related['price'], 0, ',', '.') ?></div>
                                <?php if ($related['sold_count'] > 0): ?>
                                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Terjual <?= $related['sold_count'] ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function changeImage(src, element) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    element.classList.add('active');
}

function increaseQty(max) {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

async function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch('api/cart-add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: parseInt(quantity)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Produk berhasil ditambahkan ke keranjang!');
            updateCartCount();
        } else {
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                alert(result.message || 'Gagal menambahkan ke keranjang');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    }
}

function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    localStorage.setItem('buyNow', JSON.stringify({
        product_id: productId,
        quantity: parseInt(quantity)
    }));
    window.location.href = 'cart.php?checkout=1';
}
</script>

<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "<?= htmlspecialchars($product['name']) ?>",
    "image": "<?= htmlspecialchars($images[0] ?? '') ?>",
    "description": "<?= htmlspecialchars($product['description']) ?>",
    "brand": {
        "@type": "Brand",
        "name": "Core Stone Indonesia"
    },
    "offers": {
        "@type": "Offer",
        "url": "<?= SITE_URL ?>product.php?slug=<?= htmlspecialchars($product['slug']) ?>",
        "priceCurrency": "IDR",
        "price": "<?= $product['price'] ?>",
        "availability": "<?= $product['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>",
        "seller": {
            "@type": "Organization",
            "name": "Core Stone Indonesia"
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
