<?php
// pages/products.php - Perbaikan QR Code dengan Kategori
require_once '../includes/auth.php';
requireLogin();
require_once '../config/database.php';

// Ambil kategori
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Ambil produk dengan join kategori
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name ASC
");
$products = $stmt->fetchAll();

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $barcode = trim($_POST['barcode'] ?? '');
    $message = '';
    $error = '';
    
    if ($action === 'add' && !empty($name)) {
        if (empty($barcode)) {
            $barcode = 'BAR' . date('YmdHis') . rand(10, 99);
        }
        $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock, barcode) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category_id, $price, $stock, $barcode]);
        $message = '✅ Produk berhasil ditambahkan!';
        logActivity('Menambahkan produk: ' . $name);
    } 
    elseif ($action === 'edit' && $id > 0 && !empty($name)) {
        if (!empty($barcode)) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ?, barcode = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $price, $stock, $barcode, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $price, $stock, $id]);
        }
        $message = '✅ Produk berhasil diperbarui!';
        logActivity('Mengedit produk ID: ' . $id);
    } 
    elseif ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = '✅ Produk berhasil dihapus!';
        logActivity('Menghapus produk ID: ' . $id);
    }
    
    if ($message) header('Location: products.php?success=' . urlencode($message));
    elseif ($error) header('Location: products.php?error=' . urlencode($error));
    else header('Location: products.php');
    exit();
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<?php include '../includes/header.php'; ?>

<style>
/* ============================================================
   PRODUCT GRID
   ============================================================ */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.8rem;
    margin-top: 0.5rem;
}

.product-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.8rem;
    text-align: center;
    transition: all 0.2s ease;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}

.product-card .product-name {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.2rem;
}

.product-card .product-category {
    font-size: 0.65rem;
    color: #999;
    background: #f0f0f0;
    padding: 0.1rem 0.5rem;
    border-radius: 10px;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}

.product-card .product-price {
    color: var(--secondary);
    font-weight: 700;
    font-size: 1.1rem;
}

.product-card .product-stock {
    font-size: 0.8rem;
    color: var(--gray);
}

.product-card .product-barcode {
    font-size: 0.7rem;
    color: #999;
    font-family: monospace;
    margin: 0.3rem 0;
}

.product-card .qr-code {
    margin: 0.5rem auto;
    display: flex;
    justify-content: center;
}

.product-card .qr-code canvas {
    max-width: 90px;
    max-height: 90px;
    border-radius: 4px;
}

.product-card .card-actions {
    display: flex;
    gap: 0.3rem;
    justify-content: center;
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

/* ============================================================
   QR MODAL
   ============================================================ */
.qr-modal-content {
    text-align: center;
    padding: 0.5rem 0 0.5rem 0;
}

.qr-modal-content .qr-big {
    margin: 0.5rem auto;
    display: flex;
    justify-content: center;
}

.qr-modal-content .qr-big canvas {
    max-width: 220px;
    max-height: 220px;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 10px;
    background: white;
}

.qr-modal-content .product-info {
    margin-top: 0.5rem;
}

.qr-modal-content .product-info .name {
    font-size: 1.2rem;
    font-weight: 700;
}

.qr-modal-content .product-info .category {
    font-size: 0.75rem;
    color: #999;
    background: #f0f0f0;
    padding: 0.15rem 0.6rem;
    border-radius: 10px;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    margin-top: 0.2rem;
}

.qr-modal-content .product-info .price {
    font-size: 1.3rem;
    color: var(--secondary);
    font-weight: 700;
}

.qr-modal-content .product-info .code {
    font-size: 0.8rem;
    color: var(--gray);
    font-family: monospace;
    margin-top: 0.2rem;
}

.qr-modal-content .divider {
    border: 1px dashed #ddd;
    margin: 8px auto;
    width: 80%;
}

.qr-modal-content .footer-text {
    font-size: 0.75rem;
    color: #999;
    margin-top: 5px;
}

/* ============================================================
   ALERT & MODAL
   ============================================================ */
.alert {
    padding: 0.7rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.8rem;
    font-size: 0.9rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.modal-content .form-group {
    margin-bottom: 0.8rem;
}

.modal-content .form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.modal-content .form-control {
    font-size: 0.95rem;
    min-height: 44px;
    border: 2px solid #e9ecef;
    width: 100%;
    padding: 0.5rem 0.7rem;
    border-radius: 8px;
}

.modal-content .form-control:focus {
    border-color: #1a1a2e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(26,26,46,0.08);
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 600px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    .product-card {
        padding: 0.6rem;
    }
    .product-card .product-name {
        font-size: 0.85rem;
    }
    .product-card .product-price {
        font-size: 0.95rem;
    }
    .product-card .qr-code canvas {
        max-width: 70px;
        max-height: 70px;
    }
}

@media (max-width: 400px) {
    .product-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
    }
    .product-card {
        padding: 0.5rem;
    }
    .product-card .product-name {
        font-size: 0.75rem;
    }
    .product-card .product-price {
        font-size: 0.85rem;
    }
    .product-card .qr-code canvas {
        max-width: 60px;
        max-height: 60px;
    }
}
</style>

<!-- ============================================================
   MAIN CONTENT
   ============================================================ -->
<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1.1rem;">📦 Manajemen Produk</div>
        <button class="btn btn-primary" onclick="showAddModal()" style="font-size:0.95rem;min-height:44px;">+ Tambah</button>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <div class="product-card">
            <div class="qr-code" id="qr-<?php echo $p['id']; ?>"></div>
            <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
            <div class="product-category"><?php echo htmlspecialchars($p['category_name'] ?? 'Tanpa Kategori'); ?></div>
            <div class="product-price">Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></div>
            <div class="product-stock">Stok: <?php echo $p['stock']; ?></div>
            <div class="product-barcode"><?php echo htmlspecialchars($p['barcode'] ?? '-'); ?></div>
            <div class="card-actions">
                <button class="btn btn-info btn-sm" onclick="showQRModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" style="padding:0.2rem 0.5rem;font-size:0.7rem;min-height:32px;">📱 QR</button>
                <button class="btn btn-warning btn-sm" onclick="editProduct(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>', <?php echo $p['category_id'] ?? 'null'; ?>, <?php echo $p['price']; ?>, <?php echo $p['stock']; ?>, '<?php echo addslashes($p['barcode'] ?? ''); ?>')" style="padding:0.2rem 0.5rem;font-size:0.7rem;min-height:32px;">✎</button>
                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $p['id']; ?>)" style="padding:0.2rem 0.5rem;font-size:0.7rem;min-height:32px;">✕</button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
        <div style="grid-column:1/-1;text-align:center;color:#6c757d;padding:2rem;font-size:0.95rem;">
            Belum ada produk. Klik "Tambah" untuk menambahkan produk.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
   PRODUCT MODAL
   ============================================================ -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" style="font-size:1.1rem;">Tambah Produk</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="productForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="productId">
            
            <div class="form-group">
                <label style="font-size:0.9rem;">Nama Produk *</label>
                <input type="text" name="name" id="productName" class="form-control" required>
            </div>
            <div class="form-group">
                <label style="font-size:0.9rem;">Kategori</label>
                <select name="category_id" id="productCategory" class="form-control">
                    <option value="">Tanpa Kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;">
                <div class="form-group">
                    <label style="font-size:0.9rem;">Harga *</label>
                    <input type="number" name="price" id="productPrice" class="form-control" required min="0" step="1000">
                </div>
                <div class="form-group">
                    <label style="font-size:0.9rem;">Stok *</label>
                    <input type="number" name="stock" id="productStock" class="form-control" required min="0">
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:0.9rem;">Barcode <span style="color:#6c757d;font-size:0.75rem;">(kosong = auto)</span></label>
                <input type="text" name="barcode" id="productBarcode" class="form-control" placeholder="Auto-generate">
            </div>
            <button type="submit" class="btn btn-success btn-block" style="padding:0.7rem;font-size:1rem;min-height:50px;border-radius:10px;">💾 Simpan</button>
        </form>
    </div>
</div>

<!-- ============================================================
   QR MODAL - DENGAN KATEGORI
   ============================================================ -->
<div id="qrModal" class="modal">
    <div class="modal-content" style="max-width:380px;">
        <div class="modal-header">
            <h3 style="font-size:1.1rem;">📱 QR Code Produk</h3>
            <button class="modal-close" onclick="closeQRModal()">&times;</button>
        </div>
        <div class="qr-modal-content" id="qrModalContent">
            <div class="qr-big" id="qrBigContainer"></div>
            <div class="product-info">
                <div class="name" id="qrModalName">-</div>
                <div class="category" id="qrModalCategory">-</div>
                <div class="price" id="qrModalPrice">Rp 0</div>
                <div class="divider"></div>
                <div class="code" id="qrModalBarcode">Kode: -</div>
                <div class="footer-text">Scan QR untuk menambahkan ke keranjang</div>
            </div>
            <div style="display:flex;gap:0.5rem;margin-top:0.8rem;flex-wrap:wrap;">
                <button class="btn btn-primary" onclick="printQR()" style="flex:1;font-size:0.9rem;min-height:44px;">🖨️ Print</button>
                <button class="btn btn-success" onclick="downloadQR()" style="flex:1;font-size:0.9rem;min-height:44px;">💾 Download</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
   DELETE FORM
   ============================================================ -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- ============================================================
   JAVASCRIPT
   ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// ============================================================
// VARIABLES
// ============================================================
let qrInstances = {};
let currentQRProduct = null;
let currentQRInstance = null;

// ============================================================
// GENERATE QR CODES
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($products as $p): ?>
    generateQR(<?php echo $p['id']; ?>, <?php echo json_encode($p['name']); ?>, <?php echo json_encode($p['barcode'] ?? 'NOCODE'); ?>, <?php echo $p['price']; ?>);
    <?php endforeach; ?>
});

function generateQR(productId, name, barcode, price) {
    const container = document.getElementById('qr-' + productId);
    if (!container) return;
    container.innerHTML = '';
    const qrData = name + '|' + barcode + '|Rp ' + parseInt(price).toLocaleString('id-ID');
    try {
        const qr = new QRCode(container, {
            text: qrData,
            width: 85,
            height: 85,
            colorDark: '#1a1a2e',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
        qrInstances[productId] = qr;
    } catch(e) {}
}

// ============================================================
// SHOW QR MODAL
// ============================================================
function showQRModal(product) {
    currentQRProduct = product;
    
    document.getElementById('qrModalName').textContent = product.name;
    document.getElementById('qrModalCategory').textContent = product.category_name || 'Tanpa Kategori';
    document.getElementById('qrModalPrice').textContent = 'Rp ' + parseInt(product.price).toLocaleString('id-ID');
    document.getElementById('qrModalBarcode').textContent = 'Kode: ' + (product.barcode || '-');
    
    const container = document.getElementById('qrBigContainer');
    container.innerHTML = '';
    
    // QR Data dengan kategori
    const qrData = product.name + '|' + (product.barcode || 'NOCODE') + '|' + (product.category_name || '') + '|Rp ' + parseInt(product.price).toLocaleString('id-ID');
    
    currentQRInstance = new QRCode(container, {
        text: qrData,
        width: 220,
        height: 220,
        colorDark: '#1a1a2e',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
    
    document.getElementById('qrModal').classList.add('show');
}

function closeQRModal() {
    document.getElementById('qrModal').classList.remove('show');
    if (currentQRInstance) {
        currentQRInstance.clear();
        currentQRInstance = null;
    }
    currentQRProduct = null;
}

// ============================================================
// GET QR IMAGE DATA
// ============================================================
function getQRImageData() {
    const container = document.getElementById('qrBigContainer');
    const canvas = container.querySelector('canvas');
    if (!canvas) return null;
    return canvas.toDataURL('image/png');
}

// ============================================================
// PRINT QR - DENGAN KATEGORI
// ============================================================
function printQR() {
    if (!currentQRProduct) return;
    
    const qrImageData = getQRImageData();
    if (!qrImageData) {
        alert('QR Code belum siap!');
        return;
    }
    
    const win = window.open('', '_blank', 'width=400,height=600');
    win.document.write(`
        <html>
            <head>
                <title>QR Code - ${currentQRProduct.name}</title>
                <style>
                    body {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        margin: 0;
                        font-family: Arial, sans-serif;
                        background: #f5f5f5;
                    }
                    .print-container {
                        text-align: center;
                        padding: 30px 40px;
                        background: white;
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                        max-width: 350px;
                    }
                    .print-container .qr-image {
                        margin: 10px auto;
                    }
                    .print-container .qr-image img {
                        max-width: 220px;
                        max-height: 220px;
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                        padding: 10px;
                        background: white;
                    }
                    .print-container .name {
                        font-size: 18px;
                        font-weight: bold;
                        margin-top: 10px;
                        color: #1a1a2e;
                    }
                    .print-container .category {
                        font-size: 11px;
                        color: #999;
                        background: #f0f0f0;
                        padding: 0.15rem 0.6rem;
                        border-radius: 10px;
                        display: inline-block;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        font-weight: 700;
                        margin-top: 4px;
                    }
                    .print-container .price {
                        font-size: 20px;
                        color: #e94560;
                        font-weight: bold;
                        margin-top: 2px;
                    }
                    .print-container .divider {
                        border: 1px dashed #ddd;
                        margin: 10px auto;
                        width: 80%;
                    }
                    .print-container .code {
                        font-size: 13px;
                        color: #666;
                        font-family: monospace;
                        margin-top: 5px;
                    }
                    .print-container .footer-text {
                        font-size: 11px;
                        color: #999;
                        margin-top: 10px;
                    }
                    @media print {
                        body { background: white; padding: 0; margin: 0; }
                        .print-container { 
                            box-shadow: none; 
                            border: 1px solid #ddd;
                            border-radius: 0;
                            padding: 20px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="print-container">
                    <div class="qr-image">
                        <img src="${qrImageData}" alt="QR Code ${currentQRProduct.name}" />
                    </div>
                    <div class="name">${currentQRProduct.name}</div>
                    <div class="category">${currentQRProduct.category_name || 'Tanpa Kategori'}</div>
                    <div class="price">Rp ${parseInt(currentQRProduct.price).toLocaleString('id-ID')}</div>
                    <div class="divider"></div>
                    <div class="code">${currentQRProduct.barcode || '-'}</div>
                    <div class="footer-text">Scan QR untuk menambahkan ke keranjang</div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 500);
                        }, 300);
                    }
                <\/script>
            </body>
        </html>
    `);
    win.document.close();
}

// ============================================================
// DOWNLOAD QR - DENGAN KATEGORI
// ============================================================
function downloadQR() {
    if (!currentQRProduct) {
        alert('Pilih produk terlebih dahulu!');
        return;
    }
    
    const qrImageData = getQRImageData();
    if (!qrImageData) {
        alert('QR Code belum siap!');
        return;
    }
    
    // Buat canvas untuk menggabungkan QR + info produk + KATEGORI
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Ukuran canvas
    const width = 500;
    const height = 700;
    canvas.width = width;
    canvas.height = height;
    
    // Background putih
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    
    // Border
    ctx.strokeStyle = '#e9ecef';
    ctx.lineWidth = 2;
    ctx.strokeRect(10, 10, width - 20, height - 20);
    
    // Load QR image
    const img = new Image();
    img.onload = function() {
        // Gambar QR di tengah
        const qrSize = 280;
        const qrX = (width - qrSize) / 2;
        const qrY = 30;
        ctx.drawImage(img, qrX, qrY, qrSize, qrSize);
        
        // Nama produk
        ctx.fillStyle = '#1a1a2e';
        ctx.font = 'bold 24px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(currentQRProduct.name, width/2, qrY + qrSize + 40);
        
        // KATEGORI
        const categoryText = currentQRProduct.category_name || 'Tanpa Kategori';
        ctx.fillStyle = '#999';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        // Background untuk kategori
        const catMetrics = ctx.measureText(categoryText);
        const catWidth = catMetrics.width + 20;
        const catHeight = 28;
        const catX = (width - catWidth) / 2;
        const catY = qrY + qrSize + 48;
        ctx.fillStyle = '#f0f0f0';
        ctx.beginPath();
        ctx.roundRect(catX, catY, catWidth, catHeight, 14);
        ctx.fill();
        // Text kategori
        ctx.fillStyle = '#999';
        ctx.font = 'bold 11px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(categoryText.toUpperCase(), width/2, catY + catHeight/2);
        
        // Harga
        ctx.fillStyle = '#e94560';
        ctx.font = 'bold 26px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText('Rp ' + parseInt(currentQRProduct.price).toLocaleString('id-ID'), width/2, qrY + qrSize + 125);
        
        // Garis pemisah
        ctx.strokeStyle = '#ddd';
        ctx.lineWidth = 1;
        ctx.setLineDash([5, 5]);
        ctx.beginPath();
        ctx.moveTo(40, qrY + qrSize + 140);
        ctx.lineTo(width - 40, qrY + qrSize + 140);
        ctx.stroke();
        ctx.setLineDash([]);
        
        // Barcode
        ctx.fillStyle = '#666';
        ctx.font = '16px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(currentQRProduct.barcode || '-', width/2, qrY + qrSize + 155);
        
        // Footer
        ctx.fillStyle = '#999';
        ctx.font = '13px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText('Scan QR untuk menambahkan ke keranjang', width/2, height - 20);
        
        // Download
        const link = document.createElement('a');
        link.download = 'QR_' + currentQRProduct.name + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    };
    img.src = qrImageData;
}

// ============================================================
// PRODUCT CRUD
// ============================================================
function showAddModal() {
    document.getElementById('modalTitle').textContent = '➕ Tambah Produk';
    document.getElementById('formAction').value = 'add';
    document.getElementById('productId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productCategory').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '';
    document.getElementById('productBarcode').value = '';
    document.getElementById('productModal').classList.add('show');
    setTimeout(() => document.getElementById('productName').focus(), 300);
}

function editProduct(id, name, category_id, price, stock, barcode) {
    document.getElementById('modalTitle').textContent = '✏️ Edit Produk';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('productId').value = id;
    document.getElementById('productName').value = name;
    document.getElementById('productCategory').value = category_id || '';
    document.getElementById('productPrice').value = price;
    document.getElementById('productStock').value = stock;
    document.getElementById('productBarcode').value = barcode || '';
    document.getElementById('productModal').classList.add('show');
}

function deleteProduct(id) {
    if (confirm('Yakin ingin menghapus produk ini?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('productModal').classList.remove('show');
}

// ============================================================
// CLOSE MODALS
// ============================================================
document.getElementById('productModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.getElementById('qrModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeQRModal();
});

// ============================================================
// ROUND RECT POLYFILL
// ============================================================
if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r) {
        if (r > w/2) r = w/2;
        if (r > h/2) r = h/2;
        this.moveTo(x + r, y);
        this.lineTo(x + w - r, y);
        this.quadraticCurveTo(x + w, y, x + w, y + r);
        this.lineTo(x + w, y + h - r);
        this.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        this.lineTo(x + r, y + h);
        this.quadraticCurveTo(x, y + h, x, y + h - r);
        this.lineTo(x, y + r);
        this.quadraticCurveTo(x, y, x + r, y);
        return this;
    };
}
</script>

<?php include '../includes/footer.php'; ?>