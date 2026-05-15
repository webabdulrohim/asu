<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Manajemen Pesanan';
$currentPage = 'orders';

// Handle update order status
if (isset($_POST['update_status'])) {
    try {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        $payment_status = $_POST['payment_status'] ?? 'unpaid';
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $payment_status, $order_id]);
        $success_message = "Status pesanan berhasil diperbarui";
    } catch (PDOException $e) {
        $error_message = "Gagal memperbarui status pesanan";
    }
}

// Get orders with filters
try {
    $where = ["1=1"];
    $params = [];
    
    // Filter by status
    if (isset($_GET['status']) && $_GET['status'] != '') {
        $where[] = "o.status = ?";
        $params[] = $_GET['status'];
    }
    
    // Filter by payment status
    if (isset($_GET['payment_status']) && $_GET['payment_status'] != '') {
        $where[] = "o.payment_status = ?";
        $params[] = $_GET['payment_status'];
    }
    
    // Search by order code or customer name
    if (isset($_GET['search']) && $_GET['search'] != '') {
        $where[] = "(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)";
        $search_term = "%" . $_GET['search'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Date range filter
    if (isset($_GET['date_from']) && $_GET['date_from'] != '') {
        $where[] = "o.created_at >= ?";
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (isset($_GET['date_to']) && $_GET['date_to'] != '') {
        $where[] = "o.created_at <= ?";
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }
    
    $where_clause = implode(' AND ', $where);
    
    $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE $where_clause
                           ORDER BY o.created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'processing'");
    $processing_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'shipped'");
    $shipped_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $orders = [];
    $error_message = "Gagal mengambil data pesanan";
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> Manajemen Pesanan</h1>
        <p>Kelola semua pesanan pelanggan Anda</p>
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
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($total_orders); ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($pending_orders); ?></h3>
                <p>Pesanan Pending</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($processing_orders); ?></h3>
                <p>Sedang Diproses</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($shipped_orders); ?></h3>
                <p>Dikirim</p>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <input type="text" name="search" placeholder="Cari kode order / nama / email..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           class="filter-input">
                    
                    <select name="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo (isset($_GET['status']) && $_GET['status'] == 'paid') ? 'selected' : ''; ?>>Dibayar</option>
                        <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : ''; ?>>Diproses</option>
                        <option value="shipped" <?php echo (isset($_GET['status']) && $_GET['status'] == 'shipped') ? 'selected' : ''; ?>>Dikirim</option>
                        <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : ''; ?>>Terkirim</option>
                        <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                    
                    <select name="payment_status" class="filter-select">
                        <option value="">Semua Pembayaran</option>
                        <option value="unpaid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'unpaid') ? 'selected' : ''; ?>>Belum Dibayar</option>
                        <option value="paid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'paid') ? 'selected' : ''; ?>>Sudah Dibayar</option>
                        <option value="failed" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] == 'failed') ? 'selected' : ''; ?>>Gagal</option>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>" class="filter-input">
                    <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>" class="filter-input">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode Order</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 40px; color: #ccc; margin-bottom: 10px;"></i>
                                <p style="color: #999;">Belum ada pesanan</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $order['order_code']; ?></strong>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                    <small style="color: #999;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><?php echo formatRupiah($order['grand_total']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Pending',
                                            'paid' => 'Dibayar',
                                            'processing' => 'Diproses',
                                            'shipped' => 'Dikirim',
                                            'delivered' => 'Terkirim',
                                            'cancelled' => 'Dibatalkan',
                                            'refunded' => 'Dikembalikan'
                                        ];
                                        echo $status_labels[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-payment-<?php echo $order['payment_status']; ?>">
                                        <?php 
                                        $payment_labels = [
                                            'unpaid' => 'Belum Dibayar',
                                            'paid' => 'Lunas',
                                            'failed' => 'Gagal',
                                            'refunded' => 'Refund'
                                        ];
                                        echo $payment_labels[$order['payment_status']] ?? $order['payment_status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="btn btn-sm btn-secondary" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo $order['payment_status']; ?>')" class="btn btn-sm btn-primary" title="Update Status">
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

<!-- Order Detail Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Detail Pesanan</h2>
            <span class="close-modal" onclick="closeModal('orderModal')">&times;</span>
        </div>
        <div class="modal-body" id="orderDetailContent">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Update Status Pesanan</h2>
            <span class="close-modal" onclick="closeModal('statusModal')">&times;</span>
        </div>
        <form method="POST" action="orders.php">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="modal_order_id">
                <div class="form-group">
                    <label>Status Pesanan</label>
                    <select name="status" id="modal_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Dibayar</option>
                        <option value="processing">Diproses</option>
                        <option value="shipped">Dikirim</option>
                        <option value="delivered">Terkirim</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Pembayaran</label>
                    <select name="payment_status" id="modal_payment_status" class="form-control" required>
                        <option value="unpaid">Belum Dibayar</option>
                        <option value="paid">Sudah Dibayar</option>
                        <option value="failed">Gagal</option>
                        <option value="refunded">Refund</option>
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
function viewOrder(orderId) {
    // In a real implementation, this would fetch order details via AJAX
    alert('Fitur detail pesanan akan menampilkan informasi lengkap order #' + orderId);
}

function updateOrderStatus(orderId, currentStatus, currentPaymentStatus) {
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('modal_status').value = currentStatus;
    document.getElementById('modal_payment_status').value = currentPaymentStatus;
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
.stat-info .stat-icon { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

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

.filter-input,
.filter-select {
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    min-width: 150px;
}

.filter-input {
    flex: 1;
    min-width: 200px;
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

.badge-pending { background: #fff3cd; color: #856404; }
.badge-paid { background: #d4edda; color: #155724; }
.badge-processing { background: #cce5ff; color: #004085; }
.badge-shipped { background: #b8daff; color: #004085; }
.badge-delivered { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
.badge-refunded { background: #e2e3e5; color: #383d41; }

.badge-payment-unpaid { background: #fff3cd; color: #856404; }
.badge-payment-paid { background: #d4edda; color: #155724; }
.badge-payment-failed { background: #f8d7da; color: #721c24; }
.badge-payment-refunded { background: #e2e3e5; color: #383d41; }

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
    max-width: 800px;
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
