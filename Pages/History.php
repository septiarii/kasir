<?php
// pages/history.php - Histori Transaksi
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';

// ============================================================
// CEK STRUKTUR TABEL
// ============================================================
$stmt = $pdo->query("SHOW COLUMNS FROM transactions");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Tentukan nama kolom tanggal
$dateColumn = 'created_at';
if (in_array('transaction_date', $columns)) {
    $dateColumn = 'transaction_date';
} elseif (in_array('created_at', $columns)) {
    $dateColumn = 'created_at';
} elseif (in_array('date', $columns)) {
    $dateColumn = 'date';
}

// ============================================================
// FILTER
// ============================================================
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// ============================================================
// BUILD WHERE CLAUSE
// ============================================================
$where = "1=1";

if ($filter === 'today') {
    $where = "DATE(`$dateColumn`) = CURDATE()";
} elseif ($filter === 'week') {
    $where = "YEARWEEK(`$dateColumn`, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'month') {
    $where = "DATE_FORMAT(`$dateColumn`, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
} elseif ($filter === 'custom') {
    $where = "`$dateColumn` BETWEEN '$start_date' AND '$end_date'";
}

if (!empty($search)) {
    $where .= " AND (invoice_number LIKE '%$search%' OR customer_name LIKE '%$search%')";
}

// ============================================================
// GET DATA
// ============================================================
$sql = "SELECT t.*, u.name as cashier_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE $where 
        ORDER BY t.created_at DESC";
$stmt = $pdo->query($sql);
$allTransactions = $stmt->fetchAll();

// ============================================================
// SUMMARY
// ============================================================
$sql = "SELECT 
            COUNT(*) as total,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg,
            MIN(total_amount) as min,
            MAX(total_amount) as max
        FROM transactions 
        WHERE $where";
$stmt = $pdo->query($sql);
$summary = $stmt->fetch();

// ============================================================
// PAGINATION
// ============================================================
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$totalItems = count($allTransactions);
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;
$transactions = array_slice($allTransactions, $offset, $perPage);

// ============================================================
// SETTINGS
// ============================================================
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    $settings = [
        'shop_name' => 'Toko Hermas Cell',
        'shop_address' => 'Jl. Gunung Malang, Gereneng Timur',
        'shop_phone' => '0812-3456-7890',
        'receipt_footer' => 'Barang yang sudah dibeli tidak dapat dikembalikan'
    ];
}
?>
<?php include '../includes/header.php'; ?>

<style>
/* ============================================================
   FILTER STYLE
   ============================================================ */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    padding: 0.5rem 0;
}

.filter-bar .filter-group {
    display: flex;
    gap: 0.3rem;
    flex-wrap: wrap;
    align-items: center;
}

.filter-bar .btn-filter {
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

.filter-bar .btn-filter:hover {
    border-color: #1a1a2e;
    color: #1a1a2e;
}

.filter-bar .btn-filter.active {
    background: #1a1a2e;
    border-color: #1a1a2e;
    color: white;
}

.filter-bar input[type="text"],
.filter-bar input[type="date"] {
    padding: 0.3rem 0.6rem;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 0.8rem;
    min-height: 36px;
}

.filter-bar input[type="text"]:focus,
.filter-bar input[type="date"]:focus {
    border-color: #1a1a2e;
    outline: none;
}

.filter-bar .btn {
    min-height: 36px;
    font-size: 0.8rem;
}

/* ============================================================
   SUMMARY CARDS
   ============================================================ */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.6rem;
    margin-bottom: 0.8rem;
}

.summary-cards .card-stat {
    background: white;
    padding: 0.7rem 0.6rem;
    border-radius: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    border-left: 4px solid #1a1a2e;
    text-align: center;
}

.summary-cards .card-stat .number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}

.summary-cards .card-stat .label {
    color: #6c757d;
    font-size: 0.6rem;
    margin-top: 0.15rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}

/* ============================================================
   TRANSACTION CARD (Mobile)
   ============================================================ */
.trx-card {
    display: none;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.8rem 1rem;
    margin-bottom: 0.6rem;
}

.trx-card .row {
    display: flex;
    justify-content: space-between;
    padding: 0.2rem 0;
    border-bottom: 1px solid #f5f5f5;
}

.trx-card .row:last-child {
    border-bottom: none;
}

.trx-card .row .label {
    font-size: 0.65rem;
    color: #999;
    font-weight: 600;
    text-transform: uppercase;
}

.trx-card .row .value {
    font-size: 0.85rem;
    font-weight: 600;
    text-align: right;
}

.trx-card .row .value.invoice {
    font-weight: 700;
    color: #1a1a2e;
}

.trx-card .row .value.total {
    color: #e94560;
}

.trx-card .actions {
    display: flex;
    gap: 0.4rem;
    justify-content: flex-end;
    margin-top: 0.4rem;
    padding-top: 0.4rem;
    border-top: 1px solid #f0f0f0;
}

/* ============================================================
   PAGINATION
   ============================================================ */
.pagination {
    display: flex;
    gap: 0.3rem;
    justify-content: center;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.pagination .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 0.6rem;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination .page-link:hover {
    border-color: #1a1a2e;
    color: #1a1a2e;
}

.pagination .page-link.active {
    background: #1a1a2e;
    border-color: #1a1a2e;
    color: white;
}

.pagination .page-link.disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.4rem;
    }
    .summary-cards .card-stat .number {
        font-size: 0.95rem;
    }
    .summary-cards .card-stat .label {
        font-size: 0.55rem;
    }
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-bar .filter-group {
        flex-wrap: wrap;
    }
    .filter-bar input[type="text"],
    .filter-bar input[type="date"] {
        width: 100%;
    }
    .history-table {
        display: none;
    }
    .trx-card {
        display: block;
    }
}

@media (min-width: 769px) {
    .history-card-view {
        display: none;
    }
}

@media (max-width: 400px) {
    .summary-cards {
        grid-template-columns: 1fr 1fr;
        gap: 0.3rem;
    }
    .summary-cards .card-stat .number {
        font-size: 0.8rem;
    }
}

/* ============================================================
   STRUK STYLE - SAMA DENGAN TRANSAKSI
   ============================================================ */
.struk-container {
    max-width: 320px;
    margin: 0 auto;
    background: white;
    padding: 12px 16px 14px;
    font-family: 'Courier New', 'Consolas', monospace;
    font-size: 11px;
    line-height: 1.5;
    color: #000;
    border: none;
    box-shadow: none;
}

.struk-container .struk-header {
    text-align: center;
    border-bottom: 2px double #000;
    padding-bottom: 8px;
    margin-bottom: 6px;
}

.struk-container .struk-header .shop-name {
    font-size: 16px;
    font-weight: 900;
    letter-spacing: 1px;
    color: #000;
    text-transform: uppercase;
}

.struk-container .struk-header .shop-address {
    font-size: 9px;
    color: #333;
    font-weight: 600;
    margin-top: 1px;
}

.struk-container .struk-header .shop-phone {
    font-size: 9px;
    color: #333;
    font-weight: 600;
}

.struk-container .struk-header .divider-dashed {
    border: none;
    border-top: 1.5px dashed #666;
    margin: 4px 0;
}

.struk-container .struk-info {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #333;
    padding: 1px 0;
    font-weight: 600;
}

.struk-container .struk-info .label {
    color: #555;
    font-weight: 700;
}

.struk-container .struk-items {
    margin: 4px 0;
    padding: 4px 0;
    border-top: 1.5px dashed #666;
    border-bottom: 1.5px dashed #666;
}

.struk-container .struk-items .items-header {
    display: flex;
    justify-content: space-between;
    font-size: 8px;
    text-transform: uppercase;
    color: #555;
    font-weight: 800;
    letter-spacing: 0.3px;
    padding-bottom: 2px;
    border-bottom: 1.5px dotted #999;
    margin-bottom: 2px;
}

.struk-container .struk-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1px 0;
    font-size: 10px;
    font-weight: 600;
}

.struk-container .struk-item .item-name {
    flex: 1;
    font-weight: 700;
}

.struk-container .struk-item .item-qty-price {
    color: #444;
    font-size: 9px;
    margin: 0 4px;
    white-space: nowrap;
    font-weight: 600;
}

.struk-container .struk-item .item-total {
    font-weight: 800;
    text-align: right;
    min-width: 50px;
}

.struk-container .struk-total {
    margin: 4px 0;
    padding: 4px 0;
}

.struk-container .struk-total .total-row {
    display: flex;
    justify-content: space-between;
    padding: 1px 0;
    font-size: 10px;
    font-weight: 700;
}

.struk-container .struk-total .total-row .label {
    color: #333;
    font-weight: 700;
}

.struk-container .struk-total .total-row .value {
    font-weight: 800;
}

.struk-container .struk-total .total-row.grand-total {
    font-size: 13px;
    font-weight: 900;
    border-top: 2px solid #000;
    padding-top: 4px;
    margin-top: 2px;
    color: #000;
}

.struk-container .struk-total .total-row .value.change {
    color: #28a745;
    font-weight: 900;
    font-size: 13px;
}

.struk-container .struk-footer {
    text-align: center;
    border-top: 2px double #000;
    padding-top: 8px;
    margin-top: 6px;
}

.struk-container .struk-footer .thankyou {
    font-size: 11px;
    font-weight: 900;
    color: #000;
    letter-spacing: 0.3px;
}

.struk-container .struk-footer .note {
    font-size: 7px;
    color: #555;
    margin-top: 1px;
    font-weight: 600;
}

.struk-container .struk-footer .datetime {
    font-size: 8px;
    color: #666;
    margin-top: 3px;
    font-weight: 600;
}

/* ============================================================
   PRINT - MARGIN 10mm
   ============================================================ */
@media print {
    body * {
        visibility: hidden !important;
    }
    #strukPrintArea,
    #strukPrintArea * {
        visibility: visible !important;
    }
    #strukPrintArea {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 320px;
        margin: 0 auto;
        padding: 0 10mm;
        background: white;
    }
    .no-print {
        display: none !important;
    }
    .modal {
        display: none !important;
    }
    @page {
        size: auto;
        margin: 0;
    }
}

#strukModal .modal-content {
    max-width: 360px;
    padding: 0;
}

#strukModal .modal-header {
    padding: 10px 16px;
    border-bottom: 1px solid #eee;
    margin-bottom: 0;
}

#strukModal .modal-header h3 {
    font-size: 1rem;
}

#strukModal .modal-footer {
    display: flex;
    gap: 0.5rem;
    padding: 10px 16px;
    border-top: 1px solid #eee;
    flex-wrap: wrap;
}

#strukModal .modal-footer .btn {
    flex: 1;
    font-size: 0.85rem;
    min-height: 40px;
}
</style>

<!-- ============================================================
   HEADER
   ============================================================ -->
<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1.1rem;">📋 Histori Transaksi</div>
        <a href="transactions.php" class="btn btn-success" style="font-size:0.85rem;min-height:36px;">
            🧾 Transaksi Baru
        </a>
    </div>

    <!-- ============================================================
    FILTER
    ============================================================ -->
    <div class="filter-bar">
        <div class="filter-group">
            <a href="?filter=all" class="btn-filter <?php echo $filter == 'all' ? 'active' : ''; ?>">Semua</a>
            <a href="?filter=today" class="btn-filter <?php echo $filter == 'today' ? 'active' : ''; ?>">Hari Ini</a>
            <a href="?filter=week" class="btn-filter <?php echo $filter == 'week' ? 'active' : ''; ?>">Minggu Ini</a>
            <a href="?filter=month" class="btn-filter <?php echo $filter == 'month' ? 'active' : ''; ?>">Bulan Ini</a>
            <a href="?filter=custom" class="btn-filter <?php echo $filter == 'custom' ? 'active' : ''; ?>">Kustom</a>
        </div>
        
        <?php if ($filter == 'custom'): ?>
        <form method="GET" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
            <input type="hidden" name="filter" value="custom">
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="padding:0.3rem 0.6rem;border:2px solid #e9ecef;border-radius:6px;font-size:0.8rem;min-height:36px;">
            <span style="font-size:0.8rem;color:#6c757d;">s/d</span>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="padding:0.3rem 0.6rem;border:2px solid #e9ecef;border-radius:6px;font-size:0.8rem;min-height:36px;">
            <button type="submit" class="btn btn-primary" style="min-height:36px;font-size:0.8rem;">Tampilkan</button>
        </form>
        <?php endif; ?>
        
        <form method="GET" style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;margin-left:auto;">
            <?php if ($filter != 'custom'): ?>
            <input type="hidden" name="filter" value="<?php echo $filter; ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="🔍 Cari invoice/pelanggan..." value="<?php echo htmlspecialchars($search); ?>" style="padding:0.3rem 0.6rem;border:2px solid #e9ecef;border-radius:6px;font-size:0.8rem;min-height:36px;width:200px;">
            <button type="submit" class="btn btn-primary" style="min-height:36px;font-size:0.8rem;">Cari</button>
            <?php if (!empty($search) || $filter != 'all'): ?>
            <a href="history.php" class="btn btn-danger" style="min-height:36px;font-size:0.8rem;">✕ Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ============================================================
   SUMMARY
   ============================================================ -->
<div class="summary-cards">
    <div class="card-stat" style="border-left-color:#e94560;">
        <div class="number">Rp <?php echo number_format($summary['revenue'] ?? 0, 0, ',', '.'); ?></div>
        <div class="label">Total Pendapatan</div>
    </div>
    <div class="card-stat" style="border-left-color:#28a745;">
        <div class="number"><?php echo $summary['total'] ?? 0; ?></div>
        <div class="label">Total Transaksi</div>
    </div>
    <div class="card-stat" style="border-left-color:#17a2b8;">
        <div class="number">Rp <?php echo number_format($summary['avg'] ?? 0, 0, ',', '.'); ?></div>
        <div class="label">Rata-rata</div>
    </div>
    <div class="card-stat" style="border-left-color:#ffc107;">
        <div class="number">Rp <?php echo number_format($summary['max'] ?? 0, 0, ',', '.'); ?></div>
        <div class="label">Transaksi Tertinggi</div>
    </div>
</div>

<!-- ============================================================
   TABLE VIEW (Desktop)
   ============================================================ -->
<div class="card" style="padding:0;overflow:hidden;">
    <div class="history-table table-container" style="padding:0.5rem 0;">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:2rem;color:#6c757d;">
                        <span style="font-size:2rem;display:block;">📭</span>
                        Tidak ada transaksi
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $trx): ?>
                <tr>
                    <td><strong><?php echo $trx['invoice_number']; ?></strong></td>
                    <td><?php echo htmlspecialchars($trx['cashier_name'] ?? 'Sistem'); ?></td>
                    <td><?php echo htmlspecialchars($trx['customer_name']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                    <td style="text-align:right;color:var(--secondary);font-weight:700;">
                        Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?>
                    </td>
                    <td style="text-align:center;">
                        <button class="btn btn-info btn-sm" onclick="printStruk(<?php echo $trx['id']; ?>)" title="Cetak Struk" style="padding:0.2rem 0.5rem;font-size:0.7rem;min-height:30px;">
                            🖨️
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ============================================================
    CARD VIEW (Mobile)
    ============================================================ -->
    <div class="history-card-view" style="padding:0.5rem;">
        <?php if (empty($transactions)): ?>
        <div style="text-align:center;padding:2rem;color:#6c757d;">
            <span style="font-size:2rem;display:block;">📭</span>
            Tidak ada transaksi
        </div>
        <?php else: ?>
        <?php foreach ($transactions as $trx): ?>
        <div class="trx-card">
            <div class="row">
                <span class="label">Invoice</span>
                <span class="value invoice"><?php echo $trx['invoice_number']; ?></span>
            </div>
            <div class="row">
                <span class="label">Kasir</span>
                <span class="value"><?php echo htmlspecialchars($trx['cashier_name'] ?? 'Sistem'); ?></span>
            </div>
            <div class="row">
                <span class="label">Pelanggan</span>
                <span class="value"><?php echo htmlspecialchars($trx['customer_name']); ?></span>
            </div>
            <div class="row">
                <span class="label">Tanggal</span>
                <span class="value"><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></span>
            </div>
            <div class="row">
                <span class="label">Total</span>
                <span class="value total">Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="actions">
                <button class="btn btn-info btn-sm" onclick="printStruk(<?php echo $trx['id']; ?>)" title="Cetak Struk" style="padding:0.2rem 0.6rem;font-size:0.7rem;min-height:32px;">
                    🖨️ Cetak Struk
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
   PAGINATION
   ============================================================ -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <a href="?page=<?php echo max(1, $page-1); ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">◀</a>
    
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    
    <a href="?page=<?php echo min($totalPages, $page+1); ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">▶</a>
</div>
<?php endif; ?>

<!-- ============================================================
   STRUK MODAL
   ============================================================ -->
<div id="strukModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size:1rem;">🧾 Struk Pembayaran</h3>
            <button class="modal-close" onclick="closeStrukModal()">&times;</button>
        </div>
        <div id="strukPrintArea" style="padding:0;"></div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="printStrukReceipt()">
                🖨️ Print
            </button>
            <button class="btn btn-success" onclick="closeStrukModal()">
                ✅ Selesai
            </button>
        </div>
    </div>
</div>

<script>
// ============================================================
// CETAK STRUK
// ============================================================
function printStruk(transactionId) {
    showToast('📋 Mengambil data transaksi...', 'info');
    fetch(`../api/get_transaction.php?id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStruk(data);
                showToast('✅ Struk siap dicetak', 'success');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(error => {
            showToast('❌ Terjadi kesalahan', 'danger');
            console.error(error);
        });
}

// ============================================================
// TOAST NOTIFICATION
// ============================================================
function showToast(message, type) {
    if (type === 'success') return;
    
    document.querySelectorAll('.toast-notification').forEach(el => el.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s, transform 0.5s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// ============================================================
// STRUK - SAMA DENGAN TRANSAKSI
// ============================================================
function showStruk(data) {
    const container = document.getElementById('strukPrintArea');
    
    let itemsHtml = '';
    data.items.forEach(item => {
        itemsHtml += `
            <div class="struk-item">
                <span class="item-name">${item.name}</span>
                <span class="item-qty-price">${item.qty} x ${item.price.toLocaleString('id-ID')}</span>
                <span class="item-total">${item.subtotal.toLocaleString('id-ID')}</span>
            </div>
        `;
    });
    
    const shopName = '<?php echo $settings['shop_name'] ?? 'Toko Hermas Cell'; ?>';
    const shopAddress = '<?php echo $settings['shop_address'] ?? 'Jl. Gunung Malang, Gereneng Timur'; ?>';
    const shopPhone = '<?php echo $settings['shop_phone'] ?? '0812-3456-7890'; ?>';
    const receiptFooter = '<?php echo $settings['receipt_footer'] ?? 'Barang yang sudah dibeli tidak dapat dikembalikan'; ?>';
    
    container.innerHTML = `
        <div class="struk-container">
            <div class="struk-header">
                <div class="shop-name">${shopName}</div>
                <div class="shop-address">${shopAddress}</div>
                <div class="shop-phone">Telp: ${shopPhone}</div>
                <hr class="divider-dashed">
                <div class="struk-info">
                    <span><span class="label">Invoice:</span> ${data.invoice}</span>
                    <span><span class="label">Tgl:</span> ${data.date}</span>
                </div>
                <div class="struk-info">
                    <span><span class="label">Kasir:</span> ${data.cashier}</span>
                    <span><span class="label">Pelanggan:</span> ${data.customer_name}</span>
                </div>
            </div>
            <div class="struk-items">
                <div class="items-header">
                    <span>Item</span>
                    <span>Qty x Harga</span>
                    <span>Total</span>
                </div>
                ${itemsHtml}
            </div>
            <div class="struk-total">
                <div class="total-row">
                    <span class="label">Total</span>
                    <span class="value">Rp ${data.total.toLocaleString('id-ID')}</span>
                </div>
                <div class="total-row">
                    <span class="label">Bayar</span>
                    <span class="value">Rp ${data.payment.toLocaleString('id-ID')}</span>
                </div>
                <div class="total-row grand-total">
                    <span>Kembalian</span>
                    <span class="value change">Rp ${data.change.toLocaleString('id-ID')}</span>
                </div>
            </div>
            <div class="struk-footer">
                <div class="thankyou">Terima kasih telah berbelanja!</div>
                <div class="note">${receiptFooter}</div>
                <div class="datetime">${new Date().toLocaleString('id-ID')}</div>
            </div>
        </div>
    `;
    
    document.getElementById('strukModal').classList.add('show');
}

function closeStrukModal() {
    document.getElementById('strukModal').classList.remove('show');
}

// ============================================================
// PRINT STRUK - MARGIN 10mm
// ============================================================
function printStrukReceipt() {
    const content = document.getElementById('strukPrintArea');
    const win = window.open('', '_blank', 'width=360,height=700');
    win.document.write(`
        <html>
            <head>
                <title>Struk Pembayaran</title>
                <style>
                    * { margin:0; padding:0; box-sizing:border-box; }
                    body { 
                        display:flex; 
                        justify-content:center; 
                        align-items:center; 
                        min-height:100vh; 
                        background:#f5f5f5; 
                        font-family:'Courier New','Consolas',monospace; 
                        padding:0;
                        margin:0;
                    }
                    .struk-container { 
                        max-width:320px; 
                        margin:0 auto; 
                        background:white; 
                        padding:12px 16px 14px; 
                        font-size:11px; 
                        line-height:1.5; 
                        color:#000; 
                        border:none; 
                        box-shadow:none; 
                    }
                    .struk-container .struk-header { 
                        text-align:center; 
                        border-bottom:2px double #000; 
                        padding-bottom:8px; 
                        margin-bottom:6px; 
                    }
                    .struk-container .struk-header .shop-name { 
                        font-size:16px; 
                        font-weight:900; 
                        letter-spacing:1px; 
                        color:#000; 
                        text-transform:uppercase; 
                    }
                    .struk-container .struk-header .shop-address { 
                        font-size:9px; 
                        color:#333; 
                        font-weight:600; 
                        margin-top:1px; 
                    }
                    .struk-container .struk-header .shop-phone { 
                        font-size:9px; 
                        color:#333; 
                        font-weight:600; 
                    }
                    .struk-container .struk-header .divider-dashed { 
                        border:none; 
                        border-top:1.5px dashed #666; 
                        margin:4px 0; 
                    }
                    .struk-container .struk-info { 
                        display:flex; 
                        justify-content:space-between; 
                        font-size:9px; 
                        color:#333; 
                        padding:1px 0; 
                        font-weight:600; 
                    }
                    .struk-container .struk-info .label { 
                        color:#555; 
                        font-weight:700; 
                    }
                    .struk-container .struk-items { 
                        margin:4px 0; 
                        padding:4px 0; 
                        border-top:1.5px dashed #666; 
                        border-bottom:1.5px dashed #666; 
                    }
                    .struk-container .struk-items .items-header { 
                        display:flex; 
                        justify-content:space-between; 
                        font-size:8px; 
                        text-transform:uppercase; 
                        color:#555; 
                        font-weight:800; 
                        letter-spacing:0.3px; 
                        padding-bottom:2px; 
                        border-bottom:1.5px dotted #999; 
                        margin-bottom:2px; 
                    }
                    .struk-container .struk-item { 
                        display:flex; 
                        justify-content:space-between; 
                        align-items:center; 
                        padding:1px 0; 
                        font-size:10px; 
                        font-weight:600; 
                    }
                    .struk-container .struk-item .item-name { 
                        flex:1; 
                        font-weight:700; 
                    }
                    .struk-container .struk-item .item-qty-price { 
                        color:#444; 
                        font-size:9px; 
                        margin:0 4px; 
                        white-space:nowrap; 
                        font-weight:600; 
                    }
                    .struk-container .struk-item .item-total { 
                        font-weight:800; 
                        text-align:right; 
                        min-width:50px; 
                    }
                    .struk-container .struk-total { 
                        margin:4px 0; 
                        padding:4px 0; 
                    }
                    .struk-container .struk-total .total-row { 
                        display:flex; 
                        justify-content:space-between; 
                        padding:1px 0; 
                        font-size:10px; 
                        font-weight:700; 
                    }
                    .struk-container .struk-total .total-row .label { 
                        color:#333; 
                        font-weight:700; 
                    }
                    .struk-container .struk-total .total-row .value { 
                        font-weight:800; 
                    }
                    .struk-container .struk-total .total-row.grand-total { 
                        font-size:13px; 
                        font-weight:900; 
                        border-top:2px solid #000; 
                        padding-top:4px; 
                        margin-top:2px; 
                        color:#000; 
                    }
                    .struk-container .struk-total .total-row .value.change { 
                        color:#28a745; 
                        font-weight:900; 
                        font-size:13px; 
                    }
                    .struk-container .struk-footer { 
                        text-align:center; 
                        border-top:2px double #000; 
                        padding-top:8px; 
                        margin-top:6px; 
                    }
                    .struk-container .struk-footer .thankyou { 
                        font-size:11px; 
                        font-weight:900; 
                        color:#000; 
                        letter-spacing:0.3px; 
                    }
                    .struk-container .struk-footer .note { 
                        font-size:7px; 
                        color:#555; 
                        margin-top:1px; 
                        font-weight:600; 
                    }
                    .struk-container .struk-footer .datetime { 
                        font-size:8px; 
                        color:#666; 
                        margin-top:3px; 
                        font-weight:600; 
                    }
                    
                    @media print {
                        body { 
                            background:white; 
                            padding:0; 
                            margin:0; 
                            display:block; 
                        }
                        .struk-container { 
                            border:none; 
                            box-shadow:none; 
                            margin:0 auto; 
                            padding:10px 0; 
                            max-width:320px; 
                        }
                    }
                    
                    @page {
                        size: auto;
                        margin: 0 10mm;
                    }
                </style>
            </head>
            <body>
                ${content.innerHTML}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        }, 500);
                    }
                <\/script>
            </body>
        </html>
    `);
    win.document.close();
}

// ============================================================
// CLOSE MODAL ON OUTSIDE CLICK
// ============================================================
document.getElementById('strukModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeStrukModal();
});
</script>

<?php include '../includes/footer.php'; ?>