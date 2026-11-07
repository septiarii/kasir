<?php
// pages/categories.php
require_once '../includes/auth.php';
requireLogin();
requireRole('admin');
require_once '../config/database.php';

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $message = '';
    $error = '';
    
    if ($action === 'add' && !empty($name)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Kategori "' . $name . '" sudah ada!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $message = '✅ Kategori berhasil ditambahkan!';
            logActivity('Menambahkan kategori: ' . $name);
        }
    } 
    elseif ($action === 'edit' && $id > 0 && !empty($name)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Kategori "' . $name . '" sudah ada!';
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $message = '✅ Kategori berhasil diperbarui!';
            logActivity('Mengedit kategori ID: ' . $id);
        }
    } 
    elseif ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Kategori tidak bisa dihapus karena masih memiliki produk!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = '✅ Kategori berhasil dihapus!';
            logActivity('Menghapus kategori ID: ' . $id);
        }
    }
    
    if ($message) header('Location: categories.php?success=' . urlencode($message));
    elseif ($error) header('Location: categories.php?error=' . urlencode($error));
    else header('Location: categories.php');
    exit();
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<?php include '../includes/header.php'; ?>

<style>
.category-badge { display: inline-block; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; background: #e7f3ff; color: #004085; }
.number-badge { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #f0f0f0; border-radius: 50%; font-size: 0.75rem; font-weight: 700; color: #666; }
.alert { padding: 0.7rem 1rem; border-radius: 8px; margin-bottom: 0.8rem; font-size: 0.9rem; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.action-buttons { display: flex; gap: 0.3rem; justify-content: center; align-items: center; }
.action-buttons .btn { min-width: 32px; min-height: 32px; padding: 0.15rem 0.5rem; font-size: 0.7rem; border-radius: 6px; cursor: pointer; border: none; }
.category-card { display: none; background: white; border: 1px solid #e9ecef; border-radius: 10px; padding: 0.8rem; margin-bottom: 0.6rem; }
.category-card .category-row { display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px solid #f5f5f5; }
.category-card .category-row:last-child { border-bottom: none; }
.category-card .category-label { font-size: 0.7rem; color: #999; font-weight: 600; min-width: 70px; }
.category-card .category-value { font-size: 0.9rem; font-weight: 500; text-align: right; }
.category-card .category-actions { display: flex; gap: 0.4rem; justify-content: flex-end; margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid #f0f0f0; }
@media (max-width: 768px) { .categories-table { display: none; } .category-card { display: block; } }
@media (min-width: 769px) { .categories-card-view { display: none; } }
.empty-state { text-align: center; padding: 2rem; color: #6c757d; }
.empty-state .icon { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
</style>

<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1.1rem;">📁 Manajemen Kategori</div>
        <button class="btn btn-primary" onclick="showAddModal()" style="font-size:0.9rem;min-height:44px;">+ Tambah</button>
    </div>
    
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="categories-table table-container">
        <table>
            <thead><tr><th style="width:60px;text-align:center;">#</th><th>Nama</th><th>Deskripsi</th><th style="text-align:center;">Tanggal</th><th style="text-align:center;min-width:100px;">Aksi</th></tr></thead>
            <tbody>
                <?php $no = 1; foreach ($categories as $c): ?>
                <tr>
                    <td style="text-align:center;"><span class="number-badge"><?php echo $no++; ?></span></td>
                    <td><span class="category-badge"><?php echo htmlspecialchars($c['name']); ?></span></td>
                    <td><?php echo $c['description'] ? htmlspecialchars($c['description']) : '<span style="color:#999;">—</span>'; ?></td>
                    <td style="text-align:center;font-size:0.8rem;"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                    <td style="text-align:center;">
                        <div class="action-buttons">
                            <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo $c['id']; ?>, '<?php echo addslashes($c['name']); ?>', '<?php echo addslashes($c['description'] ?? ''); ?>')">✎</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $c['id']; ?>)">✕</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <tr><td colspan="5" style="text-align:center;padding:2rem;"><div class="empty-state"><span class="icon">📁</span><div class="title">Belum ada kategori</div><div class="subtitle">Klik "Tambah" untuk menambahkan kategori baru</div></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="categories-card-view">
        <?php $no = 1; foreach ($categories as $c): ?>
        <div class="category-card">
            <div class="category-row"><span class="category-label">#</span><span class="category-value"><span class="number-badge"><?php echo $no++; ?></span></span></div>
            <div class="category-row"><span class="category-label">Nama</span><span class="category-value"><span class="category-badge"><?php echo htmlspecialchars($c['name']); ?></span></span></div>
            <div class="category-row"><span class="category-label">Deskripsi</span><span class="category-value"><?php echo htmlspecialchars($c['description'] ?? '-'); ?></span></div>
            <div class="category-row"><span class="category-label">Tanggal</span><span class="category-value" style="font-size:0.8rem;color:#999;"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></span></div>
            <div class="category-actions">
                <button class="btn btn-warning btn-sm" onclick="editCategory(<?php echo $c['id']; ?>, '<?php echo addslashes($c['name']); ?>', '<?php echo addslashes($c['description'] ?? ''); ?>')">✎ Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $c['id']; ?>)">✕ Hapus</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">📁 Tambah Kategori</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
        <form method="POST" id="categoryForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="categoryId">
            <div class="form-group"><label>Nama Kategori *</label><input type="text" name="name" id="categoryName" class="form-control" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="description" id="categoryDescription" class="form-control"></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-top:0.3rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()" style="padding:0.7rem;background:#6c757d;color:white;border:none;border-radius:10px;cursor:pointer;">❌ Batal</button>
                <button type="submit" class="btn btn-success" style="padding:0.7rem;border-radius:10px;cursor:pointer;">💾 Simpan</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = '📁 Tambah Kategori';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDescription').value = '';
    document.getElementById('categoryModal').classList.add('show');
    setTimeout(() => document.getElementById('categoryName').focus(), 300);
}
function editCategory(id, name, description) {
    document.getElementById('modalTitle').textContent = '✏️ Edit Kategori';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('categoryDescription').value = description || '';
    document.getElementById('categoryModal').classList.add('show');
}
function deleteCategory(id) {
    if (confirm('⚠️ Yakin ingin menghapus kategori ini?\n\nKategori yang memiliki produk tidak dapat dihapus.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
function closeModal() { document.getElementById('categoryModal').classList.remove('show'); }
document.getElementById('categoryModal')?.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
</script>

<?php include '../includes/footer.php'; ?>