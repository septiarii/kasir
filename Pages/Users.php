<?php
// pages/users.php
require_once '../includes/auth.php';
requireLogin();
requireRole('admin');
require_once '../config/database.php';

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'kasir';
    $message = '';
    $error = '';
    
    if ($action === 'add' && !empty($username) && !empty($name)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Username "' . $username . '" sudah digunakan!';
        } else {
            $hashedPassword = md5($password ?: '123456');
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $name, $role]);
            $message = '✅ Pengguna berhasil ditambahkan!';
            logActivity('Menambahkan user: ' . $username);
        }
    } 
    elseif ($action === 'edit' && $id > 0 && !empty($username) && !empty($name)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = '❌ Username "' . $username . '" sudah digunakan!';
        } else {
            if (!empty($password)) {
                $hashedPassword = md5($password);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashedPassword, $name, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $name, $role, $id]);
            }
            $message = '✅ Pengguna berhasil diperbarui!';
            logActivity('Mengedit user ID: ' . $id);
        }
    } 
    elseif ($action === 'delete' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            $error = '❌ Tidak bisa menghapus akun sendiri!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = '✅ Pengguna berhasil dihapus!';
            logActivity('Menghapus user ID: ' . $id);
        }
    }
    
    if ($message) header('Location: users.php?success=' . urlencode($message));
    elseif ($error) header('Location: users.php?error=' . urlencode($error));
    else header('Location: users.php');
    exit();
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<?php include '../includes/header.php'; ?>

<style>
.role-badge-custom { display: inline-block; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: white; text-align: center; min-width: 60px; }
.role-admin { background: #e94560; }
.role-owner { background: #ffc107; color: #1a1a2e; }
.role-kasir { background: #17a2b8; }
.alert { padding: 0.7rem 1rem; border-radius: 8px; margin-bottom: 0.8rem; font-size: 0.9rem; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
.current-user-badge { display: inline-block; background: #e7f3ff; color: #004085; padding: 0.1rem 0.6rem; border-radius: 12px; font-size: 0.6rem; font-weight: 600; }
.action-buttons { display: flex; gap: 0.3rem; justify-content: center; align-items: center; }
.action-buttons .btn { min-width: 32px; min-height: 32px; padding: 0.15rem 0.5rem; font-size: 0.7rem; border-radius: 6px; cursor: pointer; border: none; }
.user-card { display: none; background: white; border: 1px solid #e9ecef; border-radius: 10px; padding: 0.8rem; margin-bottom: 0.6rem; }
.user-card .user-row { display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px solid #f5f5f5; }
.user-card .user-row:last-child { border-bottom: none; }
.user-card .user-label { font-size: 0.7rem; color: #999; font-weight: 600; min-width: 70px; }
.user-card .user-value { font-size: 0.9rem; font-weight: 500; text-align: right; }
.user-card .user-actions { display: flex; gap: 0.4rem; justify-content: flex-end; margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px solid #f0f0f0; }
@media (max-width: 768px) { .users-table { display: none; } .user-card { display: block; } }
@media (min-width: 769px) { .users-card-view { display: none; } }
</style>

<div class="card">
    <div class="card-header">
        <div class="card-title" style="font-size:1.1rem;">👥 Manajemen Pengguna</div>
        <button class="btn btn-primary" onclick="showAddModal()" style="font-size:0.9rem;min-height:44px;">+ Tambah</button>
    </div>
    
    <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    
    <div class="users-table table-container">
        <table>
            <thead><tr><th>Username</th><th>Nama</th><th style="text-align:center;">Role</th><th style="text-align:center;min-width:120px;">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td style="text-align:center;"><span class="role-badge-custom role-<?php echo strtolower($u['role']); ?>"><?php echo strtoupper($u['role']); ?></span></td>
                    <td style="text-align:center;">
                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                            <span class="current-user-badge">(Anda)</span>
                        <?php else: ?>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['username']); ?>', '<?php echo addslashes($u['name']); ?>', '<?php echo $u['role']; ?>')">✎</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $u['id']; ?>)">✕</button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="users-card-view">
        <?php foreach ($users as $u): ?>
        <div class="user-card">
            <div class="user-row"><span class="user-label">Username</span><span class="user-value"><strong><?php echo htmlspecialchars($u['username']); ?></strong></span></div>
            <div class="user-row"><span class="user-label">Nama</span><span class="user-value"><?php echo htmlspecialchars($u['name']); ?></span></div>
            <div class="user-row"><span class="user-label">Role</span><span class="user-value"><span class="role-badge-custom role-<?php echo strtolower($u['role']); ?>"><?php echo strtoupper($u['role']); ?></span></span></div>
            <div class="user-actions">
                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                    <span class="current-user-badge" style="font-size:0.7rem;padding:0.2rem 0.8rem;">(Anda)</span>
                <?php else: ?>
                    <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $u['id']; ?>, '<?php echo addslashes($u['username']); ?>', '<?php echo addslashes($u['name']); ?>', '<?php echo $u['role']; ?>')">✎ Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $u['id']; ?>)">✕ Hapus</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">➕ Tambah Pengguna</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
        <form method="POST" id="userForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId">
            <div class="form-group"><label>Username *</label><input type="text" name="username" id="userUsername" class="form-control" required></div>
            <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="name" id="userName" class="form-control" required></div>
            <div class="form-group"><label>Password <span style="color:#6c757d;font-size:0.7rem;">(kosongkan jika tidak diubah)</span></label><input type="password" name="password" id="userPassword" class="form-control"></div>
            <div class="form-group"><label>Role</label>
                <select name="role" id="userRole" class="form-control">
                    <option value="kasir">👤 Kasir</option>
                    <option value="admin">🛡️ Admin</option>
                    <option value="owner">👑 Pemilik</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success btn-block" style="padding:0.7rem;font-size:1rem;min-height:50px;border-radius:10px;">💾 Simpan</button>
        </form>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = '➕ Tambah Pengguna';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
    document.getElementById('userUsername').value = '';
    document.getElementById('userName').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'kasir';
    document.getElementById('userModal').classList.add('show');
    setTimeout(() => document.getElementById('userUsername').focus(), 300);
}
function editUser(id, username, name, role) {
    document.getElementById('modalTitle').textContent = '✏️ Edit Pengguna';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = id;
    document.getElementById('userUsername').value = username;
    document.getElementById('userName').value = name;
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = role;
    document.getElementById('userModal').classList.add('show');
}
function deleteUser(id) {
    if (confirm('⚠️ Yakin ingin menghapus pengguna ini?\n\nPengguna yang dihapus tidak dapat dikembalikan.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
function closeModal() { document.getElementById('userModal').classList.remove('show'); }
document.getElementById('userModal')?.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
</script>

<?php include '../includes/footer.php'; ?>