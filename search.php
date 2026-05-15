<?php
/**
 * Search Page - Core Stone Indonesia
 * Product search functionality
 */
require_once 'includes/config.php';

$search_query = trim($_GET['q'] ?? '');
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort_by = $_GET['sort'] ?? 'relevance';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];
$types = '';

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($min_price !== null) {
    $where_conditions[] = "COALESCE(p.discount_price, p.price) >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if ($max_price !== null) {
    $where_conditions[] = "COALESCE(p.discount_price, p.price) <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$where_clause = implode(' AND ', $where_conditions);

// Sorting
$sort_options = [
    'relevance' => 'CASE WHEN p.name LIKE ? THEN 1 ELSE 2 END',
    'price_asc' => 'COALESCE(p.discount_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.discount_price, p.price) DESC',
    'newest' => 'p.created_at DESC',
    'popular' => 'p.views DESC',
    'bestselling' => 'p.sold_count DESC'
];

$order_by = $sort_options[$sort_by] ?? $sort_options['relevance'];

// Count total results
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params) && $sort_by === 'relevance') {
    $search_param = "%$search_query%";
    $count_stmt->bind_param('sss', $search_param, $search_param, $search_param);
} elseif (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_results / $per_page);

// Get products
$sql = "
    SELECT p.*, 
           c.name as category_name, c.slug as category_slug,
           (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order ASC LIMIT 1) as main_image,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id AND r.status = 'approved'
    WHERE $where_clause
    GROUP BY p.id
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$bind_params = $params;
$bind_types = $types . 'ii';
$bind_params[] = $per_page;
$bind_params[] = $offset;

if ($sort_by === 'relevance' && !empty($search_query)) {
    $search_param = "%$search_query%";
    array_splice($bind_params, count($params), 0, [$search_param]);
}

$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Get categories for filter
$categories = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = empty($search_query) ? 'Semua Produk' : 'Hasil Pencarian: ' . htmlspecialchars($search_query);
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
            </nav>
            
            <h1 class="mb-4"><?= $page_title ?></h1>
            <?php if (!empty($search_query)): ?>
                <p class="text-muted mb-4">Menampilkan <?= $total_results ?> hasil untuk "<?= htmlspecialchars($search_query) ?>"</p>
            <?php else: ?>
                <p class="text-muted mb-4">Menampilkan <?= $total_results ?> produk</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-filter"></i> Filter</h5>
                    
                    <form method="GET" action="search.php" id="filterForm">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Kategori</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control form-control-sm" 
                                           placeholder="Min" value="<?= htmlspecialchars($min_price ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control form-control-sm" 
                                           placeholder="Max" value="<?= htmlspecialchars($max_price ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                        
                        <?php if ($category_filter || $min_price || $max_price): ?>
                            <a href="search.php<?= !empty($search_query) ? '?q=' . urlencode($search_query) : '' ?>" 
                               class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-times"></i> Reset Filter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Sort Options -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-muted">Urutkan:</span>
                <select class="form-select form-select-sm" style="width: auto;" onchange="location.href=this.value">
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=relevance" <?= $sort_by === 'relevance' ? 'selected' : '' ?>>Relevansi</option>
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=popular" <?= $sort_by === 'popular' ? 'selected' : '' ?>>Paling Populer</option>
                    <option value="search.php?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=bestselling" <?= $sort_by === 'bestselling' ? 'selected' : '' ?>>Terlaris</option>
                </select>
            </div>
            
            <?php if ($products->num_rows > 0): ?>
                <div class="row">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="card product-card h-100">
                                <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                    <img src="<?= !empty($product['main_image']) ? htmlspecialchars($product['main_image']) : 'assets/images/placeholder.jpg' ?>" 
                                         class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" 
                                         style="height: 200px; object-fit: cover;">
                                </a>
                                <div class="card-body">
                                    <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                                    <h6 class="card-title mt-1">
                                        <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h6>
                                    
                                    <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                                        <div class="mb-2">
                                            <span class="text-muted text-decoration-line-through small">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                                            <div class="text-danger fw-bold">Rp <?= number_format($product['discount_price'], 0, ',', '.') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-primary fw-bold mb-2">Rp <?= number_format($product['price'], 0, ',', '.') ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-box"></i> <?= $product['stock'] ?> stok
                                        </small>
                                        <?php if ($product['review_count'] > 0): ?>
                                            <small class="text-warning">
                                                <i class="fas fa-star"></i> <?= number_format($product['avg_rating'], 1) ?> (<?= $product['review_count'] ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=<?= $sort_by ?>&page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=<?= $sort_by ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&category=<?= $category_filter ?>&min_price=<?= $min_price ?>&max_price=<?= $max_price ?>&sort=<?= $sort_by ?>&page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">Produk Tidak Ditemukan</h4>
                    <p class="text-muted">Coba kata kunci lain atau ubah filter pencarian Anda.</p>
                    <a href="products.php" class="btn btn-primary mt-3">
                        <i class="fas fa-shopping-bag"></i> Lihat Semua Produk
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
