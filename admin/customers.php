<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Manajemen Pelanggan';
$currentPage = 'customers';

// Handle update customer status
if (isset($_POST['update_status'])) {
    try {
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        $success_message = "Status pelanggan berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Gagal memperbarui status pelanggan";
    }
}

// Get customers with filters
try {
    $where = ["u.role = 'user'"];
    $params = [];
    
    // Filter by status
    if (isset($_GET['status']) && $_GET['status'] != '') {
        $where[] = "u.status = ?";
        $params[] = $_GET['status'];
    }
    
    // Search by name or email
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
        $search_term = "%" . $_GET['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $stmt = $pdo->prepare("SELECT u.*, 
                           COUNT(DISTINCT o.id) as total_orders,
                           COALESCE(SUM(o.grand_total), 0) as total_spent
                           FROM users u
                           LEFT JOIN orders o ON u.id = o.user_id
                           WHERE $where_clause
                           GROUP BY u.id
                           ORDER BY u.created_at DESC");
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'active'");
    $active_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'inactive'");
    $inactive_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $customers = [];
    $error_message = "Gagal mengambil data pelanggan";
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Manajemen Pelanggan</h1>
        <p>Kelola data pelanggan toko Anda</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($total_customers); ?></h3>
                <p>Total Pelanggan</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($active_customers); ?></h3>
                <p>Pelanggan Aktif</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($inactive_customers); ?></h3>
                <p>Pelanggan Nonaktif</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <input type="text" name="search" placeholder="Cari nama atau email..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           class="filter-input">
                    
                    <select name="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Customers Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Informasi Pelanggan</th>
                        <th>Lokasi</th>
                        <th>Total Pesanan</th>
                        <th>Total Belanja</th>
                        <th>Status</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ccc; margin-bottom: 10px;"></i>
                                <p style="color: #999;">Belum ada pelanggan</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                    </div>
                                    <small style="color: #999;"><?php echo htmlspecialchars($customer['email']); ?></small>
                                    <?php if ($customer['phone']): ?>
                                        <br>
                                        <small style="color: #666;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($customer['city']): ?>
                                        <?php echo htmlspecialchars($customer['city']); ?>
                                        <?php if ($customer['province']): ?>
                                            , <?php echo htmlspecialchars($customer['province']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="order-count"><?php echo $customer['total_orders']; ?> pesanan</span>
                                </td>
                                <td>
                                    <strong><?php echo formatRupiah($customer['total_spent']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $customer['status']; ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <button onclick='viewCustomer(<?php echo json_encode($customer); ?>)' 
                                            class="btn btn-sm btn-secondary" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick='updateCustomerStatus(<?php echo $customer["id"]; ?>, "<?php echo $customer["status"]; ?>")' 
                                            class="btn btn-sm btn-primary" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Detail Modal -->
<div id="customerModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Detail Pelanggan</h2>
            <span class="close-modal" onclick="closeModal('customerModal')">&times;</span>
        </div>
        <div class="modal-body" id="customerDetailContent">
            <!-- Content will be populated via JavaScript -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Update Status Pelanggan</h2>
            <span class="close-modal" onclick="closeModal('statusModal')">&times;</span>
        </div>
        <form method="POST" action="customers.php">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modal_user_id">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="modal_status" class="form-control" required>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Batal</button>
                <button type="submit" name="update_status" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewCustomer(customer) {
    const content = document.getElementById('customerDetailContent');
    content.innerHTML = `
        <div class="customer-detail">
            <div class="detail-section">
                <h3>Informasi Pribadi</h3>
                <div class="detail-row">
                    <span class="detail-label">Nama:</span>
                    <span class="detail-value">${customer.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${customer.email}</span>
                </div>
                ${customer.phone ? `
                <div class="detail-row">
                    <span class="detail-label">Telepon:</span>
                    <span class="detail-value">${customer.phone}</span>
                </div>
                ` : ''}
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><span class="badge badge-${customer.status}">${customer.status}</span></span>
                </div>
            </div>
            
            ${customer.address ? `
            <div class="detail-section">
                <h3>Alamat</h3>
                <div class="detail-row">
                    <span class="detail-label">Alamat:</span>
                    <span class="detail-value">${customer.address}</span>
                </div>
                ${customer.city ? `
                <div class="detail-row">
                    <span class="detail-label">Kota:</span>
                    <span class="detail-value">${customer.city}</span>
                </div>
                ` : ''}
                ${customer.province ? `
                <div class="detail-row">
                    <span class="detail-label">Provinsi:</span>
                    <span class="detail-value">${customer.province}</span>
                </div>
                ` : ''}
                ${customer.postal_code ? `
                <div class="detail-row">
                    <span class="detail-label">Kode Pos:</span>
                    <span class="detail-value">${customer.postal_code}</span>
                </div>
                ` : ''}
            </div>
            ` : ''}
            
            <div class="detail-section">
                <h3>Statistik</h3>
                <div class="detail-row">
                    <span class="detail-label">Total Pesanan:</span>
                    <span class="detail-value">${customer.total_orders || 0}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Belanja:</span>
                    <span class="detail-value"><strong>Rp ${parseInt(customer.total_spent || 0).toLocaleString('id-ID')}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Terdaftar:</span>
                    <span class="detail-value">${new Date(customer.created_at).toLocaleDateString('id-ID')}</span>
                </div>
            </div>
        </div>
    `;
    openModal('customerModal');
}

function updateCustomerStatus(userId, currentStatus) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_status').value = currentStatus;
    openModal('statusModal');
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-primary .stat-icon { background: rgba(128, 0, 32, 0.1); color: #800020; }
.stat-success .stat-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.stat-warning .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

.stat-details h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2d2d2d;
    margin-bottom: 5px;
}

.stat-details p {
    font-size: 14px;
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

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-input {
    flex: 1;
    min-width: 200px;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
}

.filter-select {
    min-width: 150px;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
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

.order-count {
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

.modal-large {
    max-width: 700px;
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
}

.customer-detail {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.detail-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.detail-section h3 {
    font-size: 16px;
    color: #800020;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    color: #2d2d2d;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .filter-input,
    .filter-select {
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>
