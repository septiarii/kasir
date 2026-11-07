<?php
// pages/transactions.php - Perbaikan Margin Print 10mm
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';

// Ambil settings
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

// Ambil produk
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name ASC
");
$products = $stmt->fetchAll();

// Ambil transaksi terbaru (5 untuk preview)
$stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 5");
$recentTransactions = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<style>
/* ============================================================
   POS STYLE
   ============================================================ */
.pos-container {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 1rem;
}

.pos-products {
    max-height: 380px;
    overflow-y: auto;
    border: 2px solid var(--gray-light);
    border-radius: 8px;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.7rem 0.8rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: var(--transition);
    gap: 0.5rem;
    min-height: 48px;
}

.product-item:active {
    background: var(--light);
    transform: scale(0.98);
}

.product-item .name {
    font-weight: 600;
    font-size: 0.95rem;
    flex: 1;
}

.product-item .price {
    color: var(--secondary);
    font-weight: 700;
    font-size: 0.9rem;
    white-space: nowrap;
}

.product-item .stock-info {
    font-size: 0.7rem;
    color: var(--gray);
    white-space: nowrap;
}

#cart {
    border: 2px solid var(--gray-light);
    border-radius: 8px;
    padding: 0.6rem;
    min-height: 150px;
    max-height: 300px;
    overflow-y: auto;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.cart-item .item-details {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
}

.cart-item .item-details .name {
    font-size: 0.9rem;
    font-weight: 600;
}

.cart-item .qty {
    width: 48px;
    padding: 0.2rem;
    border: 2px solid var(--gray-light);
    border-radius: 6px;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 600;
    min-height: 34px;
}

.cart-item .item-subtotal {
    font-weight: 700;
    color: var(--secondary);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .pos-container {
        grid-template-columns: 1fr;
        gap: 0.6rem;
    }
    .pos-products {
        max-height: 250px;
    }
    .product-item {
        padding: 0.5rem 0.6rem;
        min-height: 40px;
    }
    .product-item .name {
        font-size: 0.85rem;
    }
    .product-item .price {
        font-size: 0.8rem;
    }
    #cart {
        max-height: 200px;
        min-height: 100px;
    }
    .cart-item .item-details .name {
        font-size: 0.85rem;
    }
    .cart-item .qty {
        width: 40px;
        font-size: 0.85rem;
        min-height: 30px;
    }
}

/* ============================================================
   TOAST NOTIFICATION
   ============================================================ */
.toast-notification {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    padding: 14px 28px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    z-index: 9999;
    box-shadow: 0 6px 24px rgba(0,0,0,0.2);
    max-width: 90%;
    text-align: center;
    animation: slideUp 0.4s ease;
    border: 2px solid transparent;
}

.toast-danger {
    background: #f8d7da;
    color: #721c24;
    border-color: #dc3545;
}

.toast-warning {
    background: #fff3cd;
    color: #856404;
    border-color: #ffc107;
}

.toast-info {
    background: #cce5ff;
    color: #004085;
    border-color: #007bff;
}

@keyframes slideUp {
    from { transform: translateX(-50%) translateY(40px); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}

/* ============================================================
   STRUK STYLE - MARGIN KIRI KANAN 10mm SAAT PRINT
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

/* HEADER */
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

/* INFO */
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

/* ITEMS */
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

/* TOTAL */
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

/* FOOTER */
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
   PRINT STYLE - MARGIN KIRI KANAN 10mm
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
        <div class="card-title" style="font-size:1.2rem;">🧾 Kasir</div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button class="btn btn-info" onclick="openScanModal()" style="font-size:0.9rem;min-height:44px;">📷 Scan</button>
            <button class="btn btn-danger" onclick="clearCart()" style="font-size:0.9rem;min-height:44px;">🗑️ Reset</button>
        </div>
    </div>
    
    <div class="pos-container">
        <div>
            <div class="form-group" style="margin-bottom:0.5rem;">
                <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Cari produk..." style="font-size:1rem;padding:0.5rem 0.7rem;min-height:44px;">
            </div>
            <div class="pos-products" id="productList">
                <?php foreach ($products as $p): ?>
                <div class="product-item" data-id="<?php echo $p['id']; ?>" 
                     data-name="<?php echo htmlspecialchars($p['name']); ?>" 
                     data-price="<?php echo $p['price']; ?>" 
                     data-stock="<?php echo $p['stock']; ?>"
                     data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? ''); ?>"
                     onclick="addToCart(this)">
                    <span class="name"><?php echo htmlspecialchars($p['name']); ?></span>
                    <span class="price">Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></span>
                    <span class="stock-info">Stok:<?php echo $p['stock']; ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <p style="text-align:center;color:#6c757d;padding:2rem;font-size:0.9rem;">Belum ada produk</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <h4 style="margin-bottom:0.5rem;font-size:1.05rem;font-weight:700;">🛒 Keranjang</h4>
            <div id="cart">
                <p style="text-align:center;color:#6c757d;padding:2rem;font-size:0.9rem;">Keranjang kosong</p>
            </div>
            
            <div style="margin-top:0.8rem;">
                <div style="display:flex;justify-content:space-between;padding:0.6rem 0;border-top:3px solid #eee;">
                    <span style="font-weight:700;font-size:1.1rem;">Total:</span>
                    <span id="totalCart" style="font-size:1.5rem;font-weight:800;color:var(--secondary);">Rp 0</span>
                </div>
                <button class="btn btn-success btn-block" onclick="processTransaction()" style="padding:0.8rem;font-size:1.1rem;min-height:52px;border-radius:10px;">
                    💳 Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
   TRANSAKSI TERBARU (Preview)
   ============================================================ -->
<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1rem;">📋 Transaksi Terbaru</div>
        <a href="history.php" class="btn btn-primary btn-sm" style="font-size:0.8rem;min-height:36px;">Lihat Semua →</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTransactions as $trx): ?>
                <tr>
                    <td><strong><?php echo $trx['invoice_number']; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                    <td style="text-align:right;color:var(--secondary);font-weight:700;">
                        Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentTransactions)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;color:#6c757d;padding:1.5rem;font-size:0.9rem;">
                        Belum ada transaksi
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
   PAYMENT MODAL
   ============================================================ -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="font-size:1.2rem;">💳 Pembayaran</h3>
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <div style="text-align:center;padding:0.8rem;background:#f8f9fa;border-radius:10px;margin-bottom:1rem;">
            <span style="font-size:0.85rem;color:#6c757d;font-weight:600;">Total</span>
            <div id="paymentTotal" style="font-size:1.8rem;font-weight:800;color:var(--secondary);">Rp 0</div>
        </div>
        <div class="form-group">
            <label style="font-size:0.9rem;">Nama Pelanggan</label>
            <input type="text" id="customerName" class="form-control" value="Umum" style="font-size:1rem;min-height:46px;">
        </div>
        <div class="form-group">
            <label style="font-size:0.9rem;">Bayar</label>
            <input type="number" id="paymentAmount" class="form-control" placeholder="0" oninput="calculateChange()" style="font-size:1.1rem;min-height:46px;">
        </div>
        <div style="display:flex;justify-content:space-between;padding:0.6rem 0.8rem;background:#f8f9fa;border-radius:8px;font-size:1rem;font-weight:600;">
            <span>Kembalian:</span>
            <span id="changeAmount" style="font-weight:700;color:var(--success);font-size:1.1rem;">Rp 0</span>
        </div>
        <button class="btn btn-success btn-block" onclick="confirmPayment()" style="padding:0.8rem;margin-top:0.8rem;font-size:1.1rem;min-height:52px;border-radius:10px;">
            ✅ Konfirmasi
        </button>
    </div>
</div>

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

<!-- ============================================================
   SCAN QR MODAL
   ============================================================ -->
<div id="scanModal" class="modal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-header">
            <h3 style="font-size:1.1rem;">📷 Scan QR Code</h3>
            <button class="modal-close" onclick="closeScanModal()">&times;</button>
        </div>
        <div id="scannerContainer" style="width:100%;">
            <div id="scanInfo" style="text-align:center;padding:0.5rem;margin-bottom:0.5rem;background:#e7f3ff;border-radius:8px;border-left:4px solid #007bff;">
                <span style="font-size:0.9rem;color:#004085;">📱 Arahkan kamera ke QR Code produk</span>
            </div>
            <div id="qr-reader"></div>
            <div id="scanResult" style="margin-top:0.5rem;padding:0.7rem;border-radius:8px;display:none;font-size:0.95rem;font-weight:600;text-align:center;">
                <span id="scanResultMessage"></span>
            </div>
            <div style="display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;">
                <button class="btn btn-success" onclick="startScanner()" id="btnStartScan" style="flex:1;min-height:44px;font-size:0.9rem;">📷 Mulai Scan</button>
                <button class="btn btn-danger" onclick="stopScanner()" id="btnStopScan" style="flex:1;min-height:44px;font-size:0.9rem;display:none;">⏹ Stop</button>
            </div>
            <div style="margin-top:0.6rem;border-top:1px solid #eee;padding-top:0.6rem;">
                <label style="font-size:0.85rem;font-weight:600;color:#6c757d;">Atau masukkan kode manual</label>
                <div style="display:flex;gap:0.5rem;">
                    <input type="text" id="manualBarcode" class="form-control" placeholder="Kode produk..." style="font-size:0.95rem;min-height:44px;">
                    <button class="btn btn-primary" onclick="manualFindProduct()" style="font-size:0.9rem;min-height:44px;padding:0 1.2rem;">Cari</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ============================================================
// VARIABLES
// ============================================================
let cart = [];
let currentTotal = 0;
let html5QrCode = null;
let isScanning = false;
let isProcessingScan = false;
let lastTransactionData = null;

// ============================================================
// TOAST - HANYA ERROR & WARNING
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
// CART FUNCTIONS
// ============================================================
function addToCart(element) {
    const id = parseInt(element.dataset.id);
    const name = element.dataset.name;
    const price = parseFloat(element.dataset.price);
    const stock = parseInt(element.dataset.stock);
    
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.qty >= stock) {
            showToast('⚠️ Stok ' + name + ' tidak mencukupi!', 'warning');
            return false;
        }
        existing.qty++;
    } else {
        if (stock < 1) {
            showToast('⚠️ Stok ' + name + ' kosong!', 'warning');
            return false;
        }
        cart.push({ id, name, price, qty: 1 });
    }
    renderCart();
    return true;
}

function findProductByBarcode(barcode) {
    const productEl = document.querySelector(`.product-item[data-barcode="${barcode}"]`);
    if (productEl) return productEl;
    const allProducts = document.querySelectorAll('.product-item');
    for (let el of allProducts) {
        const productBarcode = el.dataset.barcode || '';
        if (productBarcode.toLowerCase() === barcode.toLowerCase()) return el;
        if (productBarcode.toLowerCase().includes(barcode.toLowerCase())) return el;
    }
    return null;
}

function renderCart() {
    const cartEl = document.getElementById('cart');
    if (cart.length === 0) {
        cartEl.innerHTML = '<p style="text-align:center;color:#6c757d;padding:2rem;font-size:0.9rem;">Keranjang kosong</p>';
        document.getElementById('totalCart').textContent = 'Rp 0';
        currentTotal = 0;
        return;
    }
    
    let html = '';
    let total = 0;
    cart.forEach((item, index) => {
        const subtotal = item.price * item.qty;
        total += subtotal;
        html += `
            <div class="cart-item">
                <div class="item-details">
                    <span class="name">${item.name}</span>
                    <input type="number" class="qty" value="${item.qty}" min="1" max="99" 
                           onchange="updateQty(${index}, this.value)" onfocus="this.select()">
                    <span style="font-size:0.75rem;color:#6c757d;font-weight:500;">× ${item.price.toLocaleString('id-ID')}</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span class="item-subtotal">Rp ${subtotal.toLocaleString('id-ID')}</span>
                    <button class="btn btn-danger btn-sm" style="padding:0.1rem 0.5rem;font-size:0.7rem;min-height:30px;min-width:30px;border-radius:6px;" onclick="removeFromCart(${index})">✕</button>
                </div>
            </div>
        `;
    });
    cartEl.innerHTML = html;
    document.getElementById('totalCart').textContent = 'Rp ' + total.toLocaleString('id-ID');
    currentTotal = total;
}

function updateQty(index, value) {
    const qty = parseInt(value);
    if (qty < 1 || isNaN(qty)) {
        cart.splice(index, 1);
    } else {
        cart[index].qty = qty;
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Hapus semua item dari keranjang?')) {
        cart = [];
        renderCart();
        showToast('🗑️ Keranjang telah dikosongkan', 'info');
    }
}

// ============================================================
// TRANSACTION
// ============================================================
function processTransaction() {
    if (cart.length === 0) {
        showToast('⚠️ Keranjang kosong!', 'warning');
        return;
    }
    document.getElementById('paymentTotal').textContent = 'Rp ' + currentTotal.toLocaleString('id-ID');
    document.getElementById('changeAmount').textContent = 'Rp 0';
    document.getElementById('paymentAmount').value = '';
    document.getElementById('customerName').value = 'Umum';
    document.getElementById('paymentModal').classList.add('show');
    setTimeout(() => document.getElementById('paymentAmount').focus(), 300);
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
}

function calculateChange() {
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const change = payment - currentTotal;
    const changeEl = document.getElementById('changeAmount');
    changeEl.textContent = 'Rp ' + (change >= 0 ? change.toLocaleString('id-ID') : '0');
    changeEl.style.color = change >= 0 ? 'var(--success)' : 'var(--danger)';
}

function confirmPayment() {
    const customerName = document.getElementById('customerName').value.trim() || 'Umum';
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const change = payment - currentTotal;
    
    if (payment < currentTotal) {
        showToast('⚠️ Pembayaran kurang!', 'danger');
        return;
    }

    const btn = document.querySelector('#paymentModal .btn-success');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';

    fetch('../api/save_transaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            items: cart,
            total: currentTotal,
            payment: payment,
            change: change,
            customer_name: customerName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            lastTransactionData = data;
            showStruk(data);
            cart = [];
            renderCart();
            closePaymentModal();
        } else {
            showToast('❌ Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showToast('❌ Terjadi kesalahan!', 'danger');
        console.error(error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '✅ Konfirmasi';
    });
}

// ============================================================
// STRUK
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
    setTimeout(() => {
        location.reload();
    }, 500);
}

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
                    
                    /* ===== PRINT - MARGIN KIRI KANAN 10mm ===== */
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
// QR SCANNER
// ============================================================
function openScanModal() {
    document.getElementById('scanModal').classList.add('show');
    document.getElementById('scanResult').style.display = 'none';
    document.getElementById('manualBarcode').value = '';
    document.getElementById('scanInfo').style.display = 'block';
    document.getElementById('scanInfo').innerHTML = '📱 Arahkan kamera ke QR Code produk';
    document.getElementById('scanInfo').style.background = '#e7f3ff';
    document.getElementById('scanInfo').style.borderLeft = '4px solid #007bff';
    document.getElementById('scanInfo').style.color = '#004085';
    
    stopScanner();
    document.getElementById('qr-reader').innerHTML = '';
    setTimeout(() => startScanner(), 500);
}

function closeScanModal() {
    stopScanner();
    document.getElementById('scanModal').classList.remove('show');
}

function startScanner() {
    if (isScanning) return;
    if (isProcessingScan) return;
    
    const readerContainer = document.getElementById('qr-reader');
    readerContainer.innerHTML = '';
    readerContainer.style.display = 'block';
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showScanResult('error', '❌ Browser tidak mendukung kamera. Gunakan Chrome di Android.');
        return;
    }

    showScanResult('info', '📷 Mengakses kamera... Mohon izinkan akses kamera.');
    
    document.getElementById('btnStartScan').style.display = 'none';
    document.getElementById('btnStopScan').style.display = 'flex';
    
    try {
        html5QrCode = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 15,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            function(decodedText, decodedResult) {
                if (isProcessingScan) return;
                isProcessingScan = true;
                
                console.log('✅ Scan berhasil:', decodedText);
                
                let barcode = decodedText;
                if (decodedText.includes('|')) {
                    const parts = decodedText.split('|');
                    if (parts.length >= 2) {
                        barcode = parts[1].trim();
                    }
                }
                
                const productEl = findProductByBarcode(barcode);
                
                if (productEl) {
                    const name = productEl.dataset.name;
                    if (addToCart(productEl)) {
                        showScanResult('success', '✅ ' + name + ' berhasil ditambahkan!');
                        if (navigator.vibrate) navigator.vibrate(100);
                        
                        setTimeout(function() {
                            stopScanner();
                            closeScanModal();
                            isProcessingScan = false;
                            renderCart();
                        }, 800);
                    } else {
                        isProcessingScan = false;
                    }
                } else {
                    showScanResult('error', '❌ Produk dengan kode "' + barcode + '" tidak ditemukan!');
                    document.getElementById('manualBarcode').value = barcode;
                    if (navigator.vibrate) navigator.vibrate(200);
                    isProcessingScan = false;
                }
            },
            function(errorMessage) {}
        ).then(() => {
            isScanning = true;
            showScanResult('success', '✅ Kamera aktif. Arahkan ke QR Code.');
        }).catch((err) => {
            console.error('Error starting scanner:', err);
            showScanResult('error', '❌ Gagal mengakses kamera: ' + err.message);
            document.getElementById('btnStartScan').style.display = 'flex';
            document.getElementById('btnStopScan').style.display = 'none';
            isProcessingScan = false;
        });
    } catch (err) {
        console.error('Error:', err);
        showScanResult('error', '❌ Error: ' + err.message);
        document.getElementById('btnStartScan').style.display = 'flex';
        document.getElementById('btnStopScan').style.display = 'none';
        isProcessingScan = false;
    }
}

function stopScanner() {
    isScanning = false;
    if (html5QrCode) {
        try {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
                document.getElementById('qr-reader').innerHTML = '';
                document.getElementById('qr-reader').style.display = 'none';
                document.getElementById('btnStartScan').style.display = 'flex';
                document.getElementById('btnStopScan').style.display = 'none';
            }).catch(() => {});
        } catch(e) {}
    }
    document.getElementById('btnStartScan').style.display = 'flex';
    document.getElementById('btnStopScan').style.display = 'none';
}

function showScanResult(type, message) {
    const resultEl = document.getElementById('scanResult');
    const msgEl = document.getElementById('scanResultMessage');
    const infoEl = document.getElementById('scanInfo');
    
    resultEl.style.display = 'block';
    msgEl.textContent = message;
    
    if (type === 'success') {
        resultEl.style.background = '#d4edda';
        resultEl.style.color = '#155724';
        resultEl.style.border = '2px solid #28a745';
        infoEl.style.display = 'none';
    } else if (type === 'error') {
        resultEl.style.background = '#f8d7da';
        resultEl.style.color = '#721c24';
        resultEl.style.border = '2px solid #dc3545';
        infoEl.style.display = 'none';
    } else {
        resultEl.style.background = '#cce5ff';
        resultEl.style.color = '#004085';
        resultEl.style.border = '2px solid #007bff';
        infoEl.style.display = 'block';
        infoEl.innerHTML = '📷 ' + message;
        infoEl.style.background = '#cce5ff';
        infoEl.style.borderLeft = '4px solid #007bff';
        infoEl.style.color = '#004085';
    }
}

function manualFindProduct() {
    const barcode = document.getElementById('manualBarcode').value.trim();
    if (!barcode) {
        showToast('Masukkan kode produk!', 'warning');
        return;
    }
    
    const productEl = findProductByBarcode(barcode);
    if (productEl) {
        if (addToCart(productEl)) {
            closeScanModal();
            renderCart();
        }
    } else {
        showToast('❌ Produk dengan kode "' + barcode + '" tidak ditemukan!', 'danger');
    }
}

// ============================================================
// SEARCH & KEYBOARD
// ============================================================
document.getElementById('searchProduct')?.addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(el => {
        el.style.display = el.dataset.name.toLowerCase().includes(search) ? 'flex' : 'none';
    });
});

document.getElementById('manualBarcode')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        manualFindProduct();
    }
});

document.getElementById('paymentAmount')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmPayment();
    }
});

// ============================================================
// CLOSE MODALS
// ============================================================
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});

document.getElementById('scanModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeScanModal();
});

document.getElementById('strukModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeStrukModal();
});

// ============================================================
// CLEANUP
// ============================================================
window.addEventListener('beforeunload', function() {
    stopScanner();
});
</script>

<?php include '../includes/footer.php'; ?>