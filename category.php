<?php
session_start();
require_once 'includes/config.php';

// Get category by slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

try {
    // Get category info
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active'");
    $stmt->execute([$slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        header('Location: index.php');
        exit;
    }
    
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;
    
    // Get products in this category
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.id 
                          WHERE p.category_id = ? AND p.status = 'active'
                          ORDER BY p.created_at DESC
                          LIMIT ? OFFSET ?");
    $stmt->execute([$category['id'], $perPage, $offset]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND status = 'active'");
    $countStmt->execute([$category['id']]);
    $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalProducts / $perPage);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = $category['name'] . ' - Core Stone Indonesia';
$pageDescription = $category['description'] ?? 'Koleksi ' . $category['name'];

include 'includes/header.php';
?>

<style>
.category-page {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
}

.category-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 40px 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.category-header h1 {
    font-size: 32px;
    margin-bottom: 10px;
}

.category-header p {
    font-size: 16px;
    opacity: 0.9;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.product-image {
    position: relative;
    width: 100%;
    height: 250px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ff4757;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.product-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.3s;
}

.product-card:hover .product-actions {
    opacity: 1;
}

.product-action-btn {
    width: 35px;
    height: 35px;
    border: none;
    background: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.product-action-btn:hover {
    background: var(--primary-color);
    color: white;
}

.product-info {
    padding: 15px;
}

.product-category {
    font-size: 12px;
    color: var(--primary-color);
    margin-bottom: 5px;
}

.product-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 10px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-title a {
    color: #333;
    text-decoration: none;
}

.product-price {
    margin-bottom: 10px;
}

.price-current {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-color);
}

.price-original {
    font-size: 14px;
    color: #999;
    text-decoration: line-through;
    margin-left: 8px;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #666;
    margin-bottom: 15px;
}

.product-rating .stars {
    display: inline;
    color: #ffc107;
}

.add-to-cart-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.add-to-cart-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(128, 0, 64, 0.4);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination a, .pagination span {
    padding: 10px 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.pagination a:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.pagination .active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.empty-state i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h2 {
    font-size: 24px;
    color: #666;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .product-image {
        height: 180px;
    }
    
    .category-header h1 {
        font-size: 24px;
    }
}
</style>

<div class="category-page">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" style="margin-bottom: 20px;">
        <ol class="breadcrumb" style="display: flex; gap: 8px; list-style: none; padding: 0; margin: 0;">
            <li><a href="index.php" style="color: var(--primary-color); text-decoration: none;">Home</a></li>
            <li>/</li>
            <li style="color: #666;"><?= htmlspecialchars($category['name']) ?></li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="category-header">
        <h1><i class="fas fa-gem"></i> <?= htmlspecialchars($category['name']) ?></h1>
        <?php if ($category['description']): ?>
            <p><?= htmlspecialchars($category['description']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Products Grid -->
    <?php if (!empty($products)): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <?php
                $images = json_decode($product['images'] ?? '[]', true);
                $mainImage = !empty($images[0]) ? $images[0] : 'assets/images/placeholder.jpg';
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="product.php?slug=<?= htmlspecialchars($product['slug']) ?>">
                            <img src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://via.placeholder.com/250x250?text=No+Image'">
                        </a>
                        
                        <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                            <span class="product-badge">Diskon</span>
                        <?php endif; ?>
                        
                        <div class="product-actions">
                            <button class="product-action-btn" title="Wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="product-action-btn" title="Quick View" onclick="window.location.href='product.php?slug=<?= htmlspecialchars($product['slug']) ?>'">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                        <h3 class="product-title">
                            <a href="product.php?slug=<?= htmlspecialchars($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a>
                        </h3>
                        <div class="product-price">
                            <span class="price-current">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                            <?php if ($product['discount_price']): ?>
                                <span class="price-original">Rp <?= number_format($product['discount_price'], 0, ',', '.') ?></span>
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
                            <span><i class="fas fa-shopping-bag"></i> <?= $product['sold_count'] ?> Terjual</span>
                        </div>
                        <button class="add-to-cart-btn" onclick="addToCart(<?= $product['id'] ?>)">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?slug=<?= htmlspecialchars($slug) ?>&page=<?= $page - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?slug=<?= htmlspecialchars($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?slug=<?= htmlspecialchars($slug) ?>&page=<?= $page + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h2>Belum ada produk di kategori ini</h2>
            <p>Produk akan segera hadir. Silakan cek kategori lainnya.</p>
            <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 8px;">Kembali ke Beranda</a>
        </div>
    <?php endif; ?>
</div>

<script>
async function addToCart(productId) {
    try {
        const response = await fetch('api/cart-add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
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
</script>

<?php include 'includes/footer.php'; ?>
