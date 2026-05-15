<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Manajemen Kategori';
$currentPage = 'categories';

// Handle add category
if (isset($_POST['add_category'])) {
    try {
        $name = trim($_POST['name']);
        $slug = generateSlug($name);
        $description = $_POST['description'] ?? '';
        $parent_id = $_POST['parent_id'] ?? null;
        $sort_order = $_POST['sort_order'] ?? 0;
        
        if (empty($parent_id)) {
            $parent_id = null;
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $parent_id, $sort_order]);
        $success_message = "Kategori berhasil ditambahkan";
    } catch (PDOException $e) {
        $error_message = "Gagal menambahkan kategori. Pastikan nama kategori belum digunakan.";
    }
}

// Handle update category
if (isset($_POST['update_category'])) {
    try {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $slug = generateSlug($name);
        $description = $_POST['description'] ?? '';
        $parent_id = $_POST['parent_id'] ?? null;
        $sort_order = $_POST['sort_order'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        
        if (empty($parent_id)) {
            $parent_id = null;
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, sort_order = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $parent_id, $sort_order, $status, $id]);
        $success_message = "Kategori berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Gagal memperbarui kategori";
    }
}

// Handle delete category
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        // Check if category has products
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->execute([$_GET['id']]);
        $product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($product_count > 0) {
            $error_message = "Tidak dapat menghapus kategori yang masih memiliki produk";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $success_message = "Kategori berhasil dihapus";
        }
    } catch (PDOException $e) {
        $error_message = "Gagal menghapus kategori";
    }
}

// Get categories with parent info
try {
    $stmt = $pdo->query("SELECT c.*, p.name as parent_name 
                         FROM categories c 
                         LEFT JOIN categories p ON c.parent_id = p.id 
                         ORDER BY c.sort_order ASC, c.name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get parent categories for dropdown
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
    $parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count products per category
    $stmt = $pdo->query("SELECT category_id, COUNT(*) as count FROM products GROUP BY category_id");
    $product_counts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product_counts[$row['category_id']] = $row['count'];
    }
    
} catch (PDOException $e) {
    $categories = [];
    $parent_categories = [];
    $error_message = "Gagal mengambil data kategori";
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-tags"></i> Manajemen Kategori</h1>
            <p>Kelola kategori produk batu akik Anda</p>
        </div>
        <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kategori Baru
        </button>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Categories Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Kategori Induk</th>
                        <th>Urutan</th>
                        <th>Jumlah Produk</th>
                        <th>Status</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ccc; margin-bottom: 10px;"></i>
                                <p style="color: #999;">Belum ada kategori</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <br>
                                    <small style="color: #999;">Slug: <?php echo $category['slug']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 60)); ?><?php echo strlen($category['description'] ?? '') > 60 ? '...' : ''; ?></td>
                                <td><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '-'; ?></td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td>
                                    <span class="product-count">
                                        <?php echo $product_counts[$category['id']] ?? 0; ?> produk
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $category['status']; ?>">
                                        <?php echo ucfirst($category['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick='editCategory(<?php echo json_encode($category); ?>)' 
                                                class="btn btn-sm btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=1&id=<?php echo $category['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')"
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

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Kategori</h2>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="categories.php">
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="update_category" id="update_flag" value="0">
                
                <div class="form-group">
                    <label for="name">Nama Kategori *</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="parent_id">Kategori Induk (Opsional)</label>
                    <select name="parent_id" id="parent_id" class="form-control">
                        <option value="">Tidak Ada</option>
                        <?php foreach ($parent_categories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sort_order">Urutan</label>
                    <input type="number" name="sort_order" id="sort_order" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group" id="status_group" style="display: none;">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" name="add_category" id="submit_btn" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Kategori';
    document.getElementById('edit_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('parent_id').value = '';
    document.getElementById('sort_order').value = '0';
    document.getElementById('status_group').style.display = 'none';
    document.getElementById('update_flag').value = '0';
    document.getElementById('submit_btn').setAttribute('name', 'add_category');
    openModal();
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('edit_id').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('parent_id').value = category.parent_id || '';
    document.getElementById('sort_order').value = category.sort_order;
    document.getElementById('status').value = category.status;
    document.getElementById('status_group').style.display = 'block';
    document.getElementById('update_flag').value = '1';
    document.getElementById('submit_btn').setAttribute('name', 'update_category');
    openModal();
}

function openModal() {
    document.getElementById('categoryModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

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

.table-responsive {
    overflow-x: auto;
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
    text-decoration: none;
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

.product-count {
    font-size: 13px;
    color: #666;
    background: #f8f9fa;
    padding: 4px 10px;
    border-radius: 6px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background: white;
    margin: 5% auto;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 20px;
    color: #2d2d2d;
}

.close-modal {
    font-size: 28px;
    color: #999;
    cursor: pointer;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #2d2d2d;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2d2d2d;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #800020;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>

<?php include 'includes/admin-footer.php'; ?>
