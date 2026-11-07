<?php
// pages/dashboard.php
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';

// Ambil parameter filter
$filter = $_GET['filter'] ?? 'month';
$filterLabel = '';

switch ($filter) {
    case 'week':
        $interval = "INTERVAL 7 DAY";
        $filterLabel = '1 Minggu Terakhir';
        $dateFormat = '%d %b';
        break;
    case 'month':
        $interval = "INTERVAL 30 DAY";
        $filterLabel = '1 Bulan Terakhir';
        $dateFormat = '%d %b';
        break;
    case '3months':
        $interval = "INTERVAL 90 DAY";
        $filterLabel = '3 Bulan Terakhir';
        $dateFormat = '%d %b';
        break;
    case '6months':
        $interval = "INTERVAL 180 DAY";
        $filterLabel = '6 Bulan Terakhir';
        $dateFormat = '%b %Y';
        break;
    case 'year':
        $interval = "INTERVAL 365 DAY";
        $filterLabel = '1 Tahun Terakhir';
        $dateFormat = '%b %Y';
        break;
    default:
        $interval = "INTERVAL 30 DAY";
        $filterLabel = '1 Bulan Terakhir';
        $dateFormat = '%d %b';
}

// Statistik
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions");
$stats['revenue'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()");
$stats['today_revenue'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions");
$stats['transactions'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Data grafik
if ($filter == 'week' || $filter == 'month') {
    $sql = "SELECT DATE(transaction_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
            FROM transactions 
            WHERE transaction_date >= DATE_SUB(CURDATE(), $interval)
            GROUP BY DATE(transaction_date) 
            ORDER BY transaction_date ASC";
} else {
    $sql = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') as date, COALESCE(SUM(total_amount), 0) as revenue 
            FROM transactions 
            WHERE transaction_date >= DATE_SUB(CURDATE(), $interval)
            GROUP BY YEAR(transaction_date), MONTH(transaction_date) 
            ORDER BY transaction_date ASC";
}
$stmt = $pdo->query($sql);
$chartData = $stmt->fetchAll();

// Statistik filter
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), $interval)");
$stats['filter_revenue'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), $interval)");
$stats['filter_transactions'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(AVG(total_amount), 0) as avg FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), $interval)");
$stats['filter_avg'] = $stmt->fetch()['avg'];

// Transaksi terbaru
$stmt = $pdo->query("SELECT t.*, u.name as cashier_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 8");
$recentTransactions = $stmt->fetchAll();

// Produk terlaris
$stmt = $pdo->query("SELECT p.name, SUM(td.quantity) as sold FROM transaction_details td JOIN products p ON td.product_id = p.id GROUP BY td.product_id ORDER BY sold DESC LIMIT 5");
$topProducts = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<style>
.filter-buttons { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.filter-buttons .btn-filter {
    padding: 0.3rem 0.8rem;
    border: 2px solid #e9ecef;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    color: #6c757d;
    text-decoration: none;
}
.filter-buttons .btn-filter:hover { border-color: #1a1a2e; color: #1a1a2e; }
.filter-buttons .btn-filter.active { background: #1a1a2e; border-color: #1a1a2e; color: white; }
.row-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.6rem; margin-bottom: 0.8rem; }
.stat-card { background: white; padding: 0.8rem 0.7rem; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border-left: 4px solid #1a1a2e; text-align: center; }
.stat-card .stat-number { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; }
.stat-card .stat-label { color: #6c757d; font-size: 0.6rem; margin-top: 0.15rem; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
.dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 0.8rem; }
@media (max-width: 768px) {
    .row-stats { grid-template-columns: repeat(2, 1fr); gap: 0.4rem; }
    .stat-card .stat-number { font-size: 1rem; }
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>

<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1rem;">📊 Filter Pendapatan</div>
        <div class="filter-buttons">
            <a href="?filter=week" class="btn-filter <?php echo $filter == 'week' ? 'active' : ''; ?>">1 Minggu</a>
            <a href="?filter=month" class="btn-filter <?php echo $filter == 'month' ? 'active' : ''; ?>">1 Bulan</a>
            <a href="?filter=3months" class="btn-filter <?php echo $filter == '3months' ? 'active' : ''; ?>">3 Bulan</a>
            <a href="?filter=6months" class="btn-filter <?php echo $filter == '6months' ? 'active' : ''; ?>">6 Bulan</a>
            <a href="?filter=year" class="btn-filter <?php echo $filter == 'year' ? 'active' : ''; ?>">1 Tahun</a>
        </div>
    </div>
    <div class="row-stats">
        <div class="stat-card" style="border-left-color:#e94560;">
            <div class="stat-number">Rp <?php echo number_format($stats['filter_revenue'], 0, ',', '.'); ?></div>
            <div class="stat-label"><?php echo $filterLabel; ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#28a745;">
            <div class="stat-number"><?php echo $stats['filter_transactions']; ?></div>
            <div class="stat-label">Transaksi</div>
        </div>
        <div class="stat-card" style="border-left-color:#17a2b8;">
            <div class="stat-number">Rp <?php echo number_format($stats['filter_avg'], 0, ',', '.'); ?></div>
            <div class="stat-label">Rata-rata</div>
        </div>
        <div class="stat-card" style="border-left-color:#ffc107;">
            <div class="stat-number">Rp <?php echo number_format($stats['today_revenue'], 0, ',', '.'); ?></div>
            <div class="stat-label">Hari Ini</div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="font-size:0.95rem;">📈 Pendapatan - <?php echo $filterLabel; ?></div>
        </div>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title" style="font-size:0.95rem;">🏆 Produk Terlaris</div>
        </div>
        <?php if (empty($topProducts)): ?>
            <p style="text-align:center;color:#6c757d;padding:1rem;font-size:0.85rem;">Belum ada data</p>
        <?php else: ?>
            <?php foreach ($topProducts as $idx => $p): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.3rem 0;border-bottom:1px solid #f0f0f0;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="background:#f0f0f0;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#666;"><?php echo $idx + 1; ?></span>
                    <span style="font-weight:500;font-size:0.85rem;"><?php echo htmlspecialchars($p['name']); ?></span>
                </div>
                <span style="font-weight:700;color:var(--secondary);font-size:0.8rem;"><?php echo $p['sold']; ?> terjual</span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:0.95rem;">📋 Transaksi Terbaru</div>
        <a href="transactions.php" class="btn btn-primary btn-sm" style="font-size:0.7rem;min-height:30px;">Lihat Semua</a>
    </div>
    <div class="table-container">
        <table>
            <thead><tr><th>Invoice</th><th>Kasir</th><th>Tanggal</th><th style="text-align:right;">Total</th></tr></thead>
            <tbody>
                <?php foreach ($recentTransactions as $trx): ?>
                <tr>
                    <td><strong><?php echo $trx['invoice_number']; ?></strong></td>
                    <td><?php echo htmlspecialchars($trx['cashier_name'] ?? 'Sistem'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                    <td style="text-align:right;color:var(--secondary);font-weight:700;">Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentTransactions)): ?>
                <tr><td colspan="4" style="text-align:center;color:#6c757d;padding:1.5rem;">Belum ada transaksi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    const data = <?php echo json_encode($chartData); ?>;
    const filter = '<?php echo $filter; ?>';
    const isDaily = ['week', 'month'].includes(filter);
    if (data.length === 0) {
        ctx.parentElement.innerHTML = '<p style="text-align:center;color:#6c757d;padding:2rem;">Belum ada data pendapatan</p>';
        return;
    }
    let labels = data.map(d => {
        if (isDaily) return d.date;
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const parts = d.date.split('-');
        return months[parseInt(parts[1]) - 1] + ' ' + parts[0];
    });
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan',
                data: data.map(d => parseFloat(d.revenue)),
                backgroundColor: '#1a1a2e',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { if (v >= 1000) return 'Rp ' + (v/1000).toFixed(0) + 'K'; return 'Rp ' + v; }, maxTicksLimit: 6, font: { size: 10 } } },
                x: { ticks: { maxTicksLimit: 10, font: { size: 9 } } }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>