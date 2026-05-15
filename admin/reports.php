<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Laporan & Analitik';
$currentPage = 'reports';

// Get date range from GET parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

try {
    // Sales summary for the period
    $stmt = $pdo->prepare("SELECT 
                           COUNT(*) as total_orders,
                           SUM(grand_total) as total_revenue,
                           AVG(grand_total) as avg_order_value,
                           COUNT(DISTINCT user_id) as unique_customers
                           FROM orders 
                           WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $sales_summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Daily sales data for chart
    $stmt = $pdo->prepare("SELECT DATE(created_at) as date, 
                                  COUNT(*) as order_count,
                                  SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue
                           FROM orders 
                           WHERE created_at BETWEEN ? AND ?
                           GROUP BY DATE(created_at)
                           ORDER BY date ASC");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top selling products
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, 
                                  SUM(oi.quantity) as total_sold,
                                  SUM(oi.subtotal) as total_revenue
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           LEFT JOIN categories c ON p.category_id = c.id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.created_at BETWEEN ? AND ?
                           GROUP BY p.id
                           ORDER BY total_sold DESC
                           LIMIT 10");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Order status breakdown
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count, SUM(grand_total) as total
                           FROM orders 
                           WHERE created_at BETWEEN ? AND ?
                           GROUP BY status");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $order_status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly comparison (last 6 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                                COUNT(*) as order_count,
                                SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as revenue
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month ASC");
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Category performance
    $stmt = $pdo->prepare("SELECT c.name as category_name,
                                  COUNT(DISTINCT oi.order_id) as orders_count,
                                  SUM(oi.quantity) as total_sold,
                                  SUM(oi.subtotal) as total_revenue
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           JOIN categories c ON p.category_id = c.id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.created_at BETWEEN ? AND ?
                           GROUP BY c.id
                           ORDER BY total_revenue DESC");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $category_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Gagal mengambil data laporan";
    $sales_summary = ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'unique_customers' => 0];
    $daily_sales = [];
    $top_products = [];
    $order_status_breakdown = [];
    $monthly_data = [];
    $category_performance = [];
}

include 'includes/admin-header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Laporan & Analitik</h1>
        <p>Analisis performa toko dan penjualan Anda</p>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Date Filter -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <label>Periode:</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="filter-input">
                    <span>sampai</span>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="filter-input">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($sales_summary['total_orders'] ?? 0); ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo formatRupiah($sales_summary['total_revenue'] ?? 0); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo formatRupiah($sales_summary['avg_order_value'] ?? 0); ?></h3>
                <p>Rata-rata Order</p>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo number_format($sales_summary['unique_customers'] ?? 0); ?></h3>
                <p>Pelanggan Unik</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 30px;">
        <!-- Daily Sales Chart -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Grafik Penjualan Harian</h2>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Order Status Breakdown -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-pie"></i> Status Pesanan</h2>
            </div>
            <div class="card-body">
                <canvas id="orderStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Products Table -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2><i class="fas fa-trophy"></i> Produk Terlaris (Top 10)</h2>
        </div>
        <div class="card-body table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Terjual</th>
                        <p>Pendapatan</p>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_products)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 40px;">
                                <p style="color: #999;">Tidak ada data penjualan</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_products as $index => $product): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $index + 1; ?>. <?php echo htmlspecialchars($product['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo $product['total_sold']; ?> pcs</span></td>
                                <td><strong><?php echo formatRupiah($product['total_revenue']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Category Performance -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tags"></i> Performa Kategori</h2>
        </div>
        <div class="card-body table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Jumlah Order</th>
                        <th>Total Terjual</th>
                        <th>Pendapatan</th>
                        <th>% dari Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($category_performance)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <p style="color: #999;">Tidak ada data kategori</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $total_revenue = array_sum(array_column($category_performance, 'total_revenue'));
                        ?>
                        <?php foreach ($category_performance as $category): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                <td><?php echo $category['orders_count']; ?></td>
                                <td><?php echo $category['total_sold']; ?> pcs</td>
                                <td><?php echo formatRupiah($category['total_revenue']); ?></td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $total_revenue > 0 ? ($category['total_revenue'] / $total_revenue * 100) : 0; ?>%">
                                            <?php echo number_format($total_revenue > 0 ? ($category['total_revenue'] / $total_revenue * 100) : 0, 1); ?>%
                                        </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const dailyCtx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('d/m', strtotime($item['date'])) . "'"; }, $daily_sales)); ?>],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: [<?php echo implode(',', array_column($daily_sales, 'revenue')); ?>],
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

// Order Status Chart
const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . ucfirst($item['status']) . "'"; }, $order_status_breakdown)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($order_status_breakdown, 'count')); ?>],
            backgroundColor: [
                '#ffc107',
                '#28a745',
                '#17a2b8',
                '#007bff',
                '#6c757d',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
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

.filter-row label {
    font-weight: 600;
    color: #666;
}

.filter-input {
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

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
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

.badge-info { background: #cce5ff; color: #004085; }

.progress-bar-container {
    width: 100%;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
}

.progress-bar {
    height: 24px;
    background: linear-gradient(135deg, #800020 0%, #c41e3a 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>
