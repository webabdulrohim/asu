<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Manajemen Produk';
$currentPage = 'products';

// Handle delete product
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $success_message = "Produk berhasil dihapus";
    } catch (PDOException $e) {
        $error_message = "Gagal menghapus produk";
    }
}

// Get products
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-box"></i> Manajemen Produk</h1>
            <p>Kelola katalog produk batu akik Anda</p>
        </div>
        <a href="product-add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Produk Baru
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body" style="padding: 20px;">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Cari produk..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                       style="flex: 1; min-width: 200px; padding: 10px 15px; border: 1px solid #e0e0e0; border-radius: 8px;">
                
                <select name="category" style="min-width: 150px; padding: 10px 15px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status" style="min-width: 150px; padding: 10px 15px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Semua Status</option>
                    <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                    <option value="out_of_stock" <?php echo (isset($_GET['status']) && $_GET['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Habis Stok</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Gambar</th>
                        <th>Informasi Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Terjual</th>
                        <th>Status</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ccc; margin-bottom: 10px;"></i>
                                <p style="color: #999;">Belum ada produk</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                            $images = json_decode($product['images'], true);
                            $main_image = !empty($images[0]) ? $images[0] : 'default-product.jpg';
                            $price = $product['discount_price'] ?? $product['price'];
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo SITE_URL; ?>/uploads/products/<?php echo $main_image; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br>
                                    <small style="color: #999;">SKU: <?php echo $product['sku'] ?? '-'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                <td>
                                    <?php echo formatRupiah($price); ?>
                                    <?php if ($product['discount_price']): ?>
                                        <br>
                                        <small style="text-decoration: line-through; color: #999;">
                                            <?php echo formatRupiah($product['price']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo $product['stock'] > 0 ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['sold_count']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $product['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'active' => 'Aktif',
                                            'inactive' => 'Nonaktif',
                                            'out_of_stock' => 'Habis Stok'
                                        ];
                                        echo $status_labels[$product['status']] ?? $product['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=1&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.admin-content {
    padding: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #2d2d2d;
    margin-bottom: 5px;
}

.page-header p {
    color: #666;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-body {
    padding: 25px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.data-table th {
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    background: #f8f9fa;
}

.data-table td {
    font-size: 14px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #800020 0%, #c41e3a 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(128, 0, 32, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #e2e3e5; color: #383d41; }
.badge-out_of_stock { background: #f8d7da; color: #721c24; }
</style>

<?php include 'includes/admin-footer.php'; ?>
