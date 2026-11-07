<?php
// pages/reports.php
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';

$period = $_GET['period'] ?? 'today';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

switch ($period) {
    case 'today': $where = "DATE(transaction_date) = CURDATE()"; break;
    case 'week': $where = "YEARWEEK(transaction_date, 1) = YEARWEEK(CURDATE(), 1)"; break;
    case 'month': $where = "DATE_FORMAT(transaction_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')"; break;
    case 'custom': $where = "transaction_date BETWEEN '$start_date' AND '$end_date'"; break;
    default: $where = "DATE(transaction_date) = CURDATE()";
}

// Transaksi
$stmt = $pdo->query("SELECT t.*, u.name as cashier_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.created_at DESC");
$transactions = $stmt->fetchAll();

// Summary
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(total_amount) as revenue, AVG(total_amount) as avg, MIN(total_amount) as min, MAX(total_amount) as max FROM transactions WHERE $where");
$summary = $stmt->fetch();

// Produk terlaris
$stmt = $pdo->query("SELECT p.name, SUM(td.quantity) as sold, SUM(td.subtotal) as revenue FROM transaction_details td JOIN products p ON td.product_id = p.id JOIN transactions t ON td.transaction_id = t.id WHERE $where GROUP BY td.product_id ORDER BY sold DESC LIMIT 5");
$topProducts = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<style>
.filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; align-items: end; }
.filter-form label { font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.15rem; color: #6c757d; }
.filter-form .form-control { min-height: 40px; font-size: 0.85rem; }
@media (max-width: 500px) { .filter-form { grid-template-columns: 1fr 1fr; } }
</style>

<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1.1rem;">📊 Laporan Penjualan</div>
    </div>
    
    <form method="GET" class="filter-form">
        <div>
            <label>Periode</label>
            <select name="period" class="form-control" onchange="this.form.submit()">
                <option value="today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Minggu Ini</option>
                <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Bulan Ini</option>
                <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>Kustom</option>
            </select>
        </div>
        <?php if ($period === 'custom'): ?>
        <div>
            <label>Dari</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
        </div>
        <div>
            <label>Sampai</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="width:100%;min-height:40px;">Tampilkan</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Stats -->
<div class="row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:0.6rem;margin-bottom:0.8rem;">
    <div class="stat-card"><div class="stat-number">Rp <?php echo number_format($summary['revenue'] ?? 0, 0, ',', '.'); ?></div><div class="stat-label">Total Pendapatan</div></div>
    <div class="stat-card" style="border-left-color:#28a745;"><div class="stat-number"><?php echo $summary['total'] ?? 0; ?></div><div class="stat-label">Total Transaksi</div></div>
    <div class="stat-card" style="border-left-color:#17a2b8;"><div class="stat-number">Rp <?php echo number_format($summary['avg'] ?? 0, 0, ',', '.'); ?></div><div class="stat-label">Rata-rata</div></div>
</div>

<!-- Transaksi -->
<div class="card">
    <div class="card-header"><div class="card-title" style="font-size:1rem;">📋 Detail Transaksi</div></div>
    <div class="table-container">
        <table>
            <thead><tr><th>Invoice</th><th>Kasir</th><th>Tanggal</th><th style="text-align:right;">Total</th></tr></thead>
            <tbody>
                <?php foreach ($transactions as $trx): ?>
                <tr>
                    <td><strong><?php echo $trx['invoice_number']; ?></strong></td>
                    <td><?php echo htmlspecialchars($trx['cashier_name'] ?? 'Sistem'); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                    <td style="text-align:right;color:var(--secondary);font-weight:700;">Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="4" style="text-align:center;color:#6c757d;padding:1.5rem;">Tidak ada transaksi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Produk Terlaris -->
<div class="card">
    <div class="card-header"><div class="card-title" style="font-size:1rem;">🏆 Produk Terlaris</div></div>
    <?php if (empty($topProducts)): ?>
        <p style="text-align:center;color:#6c757d;padding:1rem;">Belum ada data</p>
    <?php else: ?>
        <?php foreach ($topProducts as $p): ?>
        <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:1px solid #f0f0f0;font-size:0.9rem;">
            <span><?php echo htmlspecialchars($p['name']); ?></span>
            <span><?php echo $p['sold']; ?> terjual · Rp <?php echo number_format($p['revenue'], 0, ',', '.'); ?></span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>