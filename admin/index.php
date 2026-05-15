<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Dashboard Admin';
$currentPage = 'dashboard';

// Get statistics
try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue (paid orders)
    $stmt = $pdo->query("SELECT SUM(grand_total) as revenue FROM orders WHERE payment_status = 'paid'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent orders
    $stmt = $pdo->query("SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         LEFT JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 10");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top selling products
    $stmt = $pdo->query("SELECT p.*, c.name as category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.status = 'active' 
                         ORDER BY p.sold_count DESC 
                         LIMIT 5");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly sales data (last 6 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                                COUNT(*) as order_count, 
                                SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month ASC");
    $monthly_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Gagal mengambil data statistik";
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>Selamat datang di panel administrasi Core Stone Indonesia</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($total_orders); ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo formatRupiah($total_revenue); ?></h3>
                <p>Total Pendapatan</p>
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
                <h3><?php echo number_format($total_products); ?></h3>
                <p>Produk Aktif</p>
            </div>
        </div>
        
        <div class="stat-card stat-danger">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($total_customers); ?></h3>
                <p>Total Pelanggan</p>
            </div>
        </div>
    </div>
    
    <!-- Charts and Tables -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Pesanan Terbaru</h2>
                <a href="orders.php" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode Order</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_code']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo formatRupiah($order['grand_total']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-star"></i> Produk Terlaris</h2>
            </div>
            <div class="card-body">
                <div class="product-list">
                    <?php foreach ($top_products as $product): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p><?php echo htmlspecialchars($product['category_name']); ?></p>
                        </div>
                        <div class="product-stats">
                            <span class="sold-count"><?php echo $product['sold_count']; ?> terjual</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Sales Chart -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Grafik Penjualan (6 Bulan Terakhir)</h2>
        </div>
        <div class="card-body">
            <canvas id="salesChart" height="80"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'".date('M Y', strtotime($item['month'].'-01'))."'"; }, $monthly_sales)); ?>],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: [<?php echo implode(',', array_column($monthly_sales, 'revenue')); ?>],
            borderColor: '#800020',
            backgroundColor: 'rgba(128, 0, 32, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

<style>
.admin-content {
    padding: 30px;
}

.page-header {
    margin-bottom: 30px;
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
.stat-danger .stat-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

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

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 18px;
    color: #2d2d2d;
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
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.data-table th {
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
}

.data-table td {
    font-size: 14px;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-pending { background: #fff3cd; color: #856404; }
.badge-paid,
.badge-processing { background: #d4edda; color: #155724; }
.badge-shipped { background: #cce5ff; color: #004085; }
.badge-delivered { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }

.product-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.product-info h4 {
    font-size: 14px;
    color: #2d2d2d;
    margin-bottom: 5px;
}

.product-info p {
    font-size: 12px;
    color: #666;
}

.sold-count {
    font-size: 13px;
    color: #800020;
    font-weight: 600;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}
</style>

<?php include 'includes/admin-footer.php'; ?>
