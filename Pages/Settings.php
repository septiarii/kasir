<?php
// pages/settings.php
require_once '../includes/auth.php';
requireLogin();
requireRole('admin');
require_once '../config/database.php';

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = '';
    $error = '';
    
    if ($action === 'save_settings') {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8);
                $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
                $stmt->execute([$value, $settingKey]);
            }
        }
        $message = '✅ Pengaturan berhasil disimpan!';
        logActivity('Menyimpan pengaturan sistem');
    } 
    elseif ($action === 'reset_settings') {
        $defaults = [
            'shop_name' => 'Toko Hermas Cell',
            'shop_address' => 'Jl. Gunung Malang, Gereneng Timur',
            'shop_phone' => '0812-3456-7890',
            'shop_email' => 'hermascell@gmail.com',
            'app_name' => 'Hermas POS',
            'app_footer' => '© 2026 Hermas POS | v1.0',
            'currency' => 'Rp',
            'tax_percent' => '0',
            'receipt_footer' => 'Barang yang sudah dibeli tidak dapat dikembalikan',
            'backup_auto' => '1'
        ];
        foreach ($defaults as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
            $stmt->execute([$value, $key]);
        }
        $message = '✅ Pengaturan berhasil direset ke default!';
        logActivity('Reset pengaturan ke default');
    } 
    elseif ($action === 'backup_db') {
        $backupDir = __DIR__ . '/../backup/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
        $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $sql = "-- Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch();
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $row[1] . ";\n\n";
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    foreach ($rows as $row) {
                        $values = array_map(function($v) use ($pdo) {
                            if ($v === null) return 'NULL';
                            return "'" . addslashes($v) . "'";
                        }, array_values($row));
                        $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            file_put_contents($backupFile, $sql);
            $message = '✅ Database berhasil di-backup! File: ' . basename($backupFile);
            logActivity('Backup database: ' . basename($backupFile));
        } catch (Exception $e) {
            $error = '❌ Gagal backup database: ' . $e->getMessage();
        }
    }
    
    if ($message) header('Location: settings.php?success=' . urlencode($message));
    elseif ($error) header('Location: settings.php?error=' . urlencode($error));
    else header('Location: settings.php');
    exit();
}

// Ambil data settings
$settings = getSettings($pdo);
$groupedSettings = [];
foreach ($settings as $key => $value) {
    $stmt = $pdo->prepare("SELECT `group`, description FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    $group = $row ? $row['group'] : 'general';
    $desc = $row ? $row['description'] : '';
    $groupedSettings[$group][] = ['key' => $key, 'value' => $value, 'description' => $desc];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<?php include '../includes/header.php'; ?>

<style>
.settings-container { max-width: 820px; margin: 0 auto; }
.settings-group { margin-bottom: 1.5rem; background: white; border-radius: 10px; border: 1px solid #e9ecef; overflow: hidden; }
.settings-group .group-header { padding: 0.7rem 1.2rem; background: #f8f9fa; border-bottom: 2px solid #e9ecef; display: flex; align-items: center; gap: 0.6rem; }
.settings-group .group-header .icon { font-size: 1.2rem; }
.settings-group .group-header .title { font-size: 1rem; font-weight: 700; color: var(--primary); }
.settings-group .group-header .badge { margin-left: auto; font-size: 0.6rem; background: var(--gray-light); color: var(--gray); padding: 0.15rem 0.6rem; border-radius: 12px; font-weight: 600; }
.setting-item { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 1.2rem; border-bottom: 1px solid #f5f5f5; gap: 1rem; transition: background 0.2s; }
.setting-item:hover { background: #fafbfc; }
.setting-item:last-child { border-bottom: none; }
.setting-item .setting-left { flex: 1; min-width: 140px; }
.setting-item .setting-left .label { font-weight: 600; font-size: 0.9rem; color: var(--primary); display: block; }
.setting-item .setting-left .desc { font-size: 0.7rem; color: #999; margin-top: 1px; }
.setting-item .setting-right { flex: 1; max-width: 320px; min-width: 150px; }
.setting-item .setting-right input, .setting-item .setting-right select, .setting-item .setting-right textarea {
    width: 100%; padding: 0.4rem 0.6rem; border: 1.5px solid #e9ecef; border-radius: 6px; font-size: 0.85rem;
    transition: all 0.2s; background: white; font-family: inherit;
}
.setting-item .setting-right input:focus, .setting-item .setting-right select:focus, .setting-item .setting-right textarea:focus {
    border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(26,26,46,0.06);
}
.setting-item .setting-right textarea { min-height: 50px; resize: vertical; }
.setting-item .setting-right select { min-height: 38px; appearance: auto; }
.settings-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-top: 1rem; padding: 1rem 1.2rem; background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef; }
.settings-actions .btn { flex: 1; min-height: 44px; font-size: 0.9rem; font-weight: 600; border-radius: 8px; }
.log-container { max-height: 200px; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 0.5rem 0.8rem; border: 1px solid #e9ecef; font-family: 'Courier New', monospace; font-size: 0.7rem; }
.log-container .log-item { padding: 0.2rem 0; border-bottom: 1px solid #f0f0f0; color: #666; }
.log-container .log-item:last-child { border-bottom: none; }
.log-container .log-empty { text-align: center; color: #999; padding: 1rem; font-size: 0.8rem; }
.alert { padding: 0.7rem 1rem; border-radius: 8px; margin-bottom: 0.8rem; font-size: 0.9rem; }
.alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
.alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
@media (max-width: 768px) {
    .setting-item { flex-direction: column; align-items: stretch; gap: 0.3rem; padding: 0.7rem 1rem; }
    .setting-item .setting-left { min-width: unset; }
    .setting-item .setting-right { max-width: 100%; min-width: unset; }
    .settings-actions { flex-direction: column; }
    .settings-actions .btn { width: 100%; justify-content: center; }
}
</style>

<div class="settings-container">
    <div class="card" style="padding:0;overflow:hidden;">
        <div class="card-header" style="padding:1rem 1.5rem;background:#f8f9fa;border-bottom:2px solid #e9ecef;margin-bottom:0;">
            <div class="card-title" style="font-size:1.2rem;">⚙️ Pengaturan</div>
            <button class="btn btn-warning btn-sm" onclick="resetSettings()" style="font-size:0.8rem;min-height:36px;">🔄 Reset</button>
        </div>
        <div style="padding:1rem 1.5rem;">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                <?php 
                $groupLabels = ['shop' => ['icon' => '🏪', 'label' => 'Toko'], 'system' => ['icon' => '⚙️', 'label' => 'Sistem'], 'receipt' => ['icon' => '🧾', 'label' => 'Struk'], 'backup' => ['icon' => '💾', 'label' => 'Backup']];
                $labelMap = ['shop_name' => 'Nama Toko', 'shop_address' => 'Alamat Toko', 'shop_phone' => 'Telepon', 'shop_email' => 'Email', 'app_name' => 'Nama Aplikasi', 'app_footer' => 'Footer Aplikasi', 'currency' => 'Mata Uang', 'tax_percent' => 'Pajak', 'receipt_footer' => 'Footer Struk', 'backup_auto' => 'Auto Backup'];
                ?>
                <?php foreach ($groupedSettings as $group => $items): ?>
                    <?php $groupInfo = $groupLabels[$group] ?? ['icon' => '📋', 'label' => ucfirst($group)]; ?>
                    <div class="settings-group">
                        <div class="group-header">
                            <span class="icon"><?php echo $groupInfo['icon']; ?></span>
                            <span class="title"><?php echo $groupInfo['label']; ?></span>
                            <span class="badge"><?php echo count($items); ?> pengaturan</span>
                        </div>
                        <?php foreach ($items as $item): ?>
                            <?php 
                                $key = $item['key']; $value = htmlspecialchars($item['value']); $desc = htmlspecialchars($item['description'] ?? '');
                                $label = $labelMap[$key] ?? ucwords(str_replace('_', ' ', $key));
                            ?>
                            <div class="setting-item">
                                <div class="setting-left">
                                    <span class="label"><?php echo $label; ?></span>
                                    <?php if ($desc): ?><span class="desc"><?php echo $desc; ?></span><?php endif; ?>
                                </div>
                                <div class="setting-right">
                                    <?php if ($key === 'shop_address' || $key === 'receipt_footer'): ?>
                                        <textarea name="setting_<?php echo $key; ?>" rows="2"><?php echo $value; ?></textarea>
                                    <?php elseif ($key === 'tax_percent'): ?>
                                        <select name="setting_<?php echo $key; ?>">
                                            <?php for ($i = 0; $i <= 20; $i += 1): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $value == $i ? 'selected' : ''; ?>><?php echo $i; ?>%</option>
                                            <?php endfor; ?>
                                        </select>
                                    <?php elseif ($key === 'backup_auto'): ?>
                                        <select name="setting_<?php echo $key; ?>">
                                            <option value="1" <?php echo $value == '1' ? 'selected' : ''; ?>>✅ Aktif</option>
                                            <option value="0" <?php echo $value == '0' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                                        </select>
                                    <?php elseif ($key === 'currency'): ?>
                                        <select name="setting_<?php echo $key; ?>">
                                            <option value="Rp" <?php echo $value == 'Rp' ? 'selected' : ''; ?>>Rp (Rupiah)</option>
                                            <option value="$" <?php echo $value == '$' ? 'selected' : ''; ?>>$ (Dollar)</option>
                                            <option value="€" <?php echo $value == '€' ? 'selected' : ''; ?>>€ (Euro)</option>
                                            <option value="£" <?php echo $value == '£' ? 'selected' : ''; ?>>£ (Pound)</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" name="setting_<?php echo $key; ?>" value="<?php echo $value; ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="settings-actions">
                    <button type="submit" class="btn btn-success">💾 Simpan Pengaturan</button>
                    <button type="button" class="btn btn-info" onclick="backupDatabase()">💾 Backup Database</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'" style="background:#6c757d;color:white;">↩️ Kembali</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top:1rem;">
        <div class="card-header">
            <div class="card-title" style="font-size:1rem;">📋 Log Aktivitas</div>
            <button class="btn btn-secondary btn-sm" onclick="refreshLog()" style="font-size:0.7rem;min-height:30px;background:#6c757d;color:white;border:none;border-radius:6px;padding:0.2rem 0.8rem;">🔄 Refresh</button>
        </div>
        <div class="log-container" id="logContainer">
            <?php
            $logFile = __DIR__ . '/../logs/activity.log';
            if (file_exists($logFile)) {
                $logs = array_reverse(file($logFile));
                $logs = array_slice($logs, 0, 15);
                if (!empty($logs)) {
                    foreach ($logs as $log) { $log = trim($log); if (!empty($log)) echo '<div class="log-item">' . htmlspecialchars($log) . '</div>'; }
                } else { echo '<div class="log-empty">📭 Belum ada aktivitas</div>'; }
            } else { echo '<div class="log-empty">📭 Log aktivitas belum tersedia</div>'; }
            ?>
        </div>
    </div>
</div>

<form id="resetForm" method="POST" style="display:none;"><input type="hidden" name="action" value="reset_settings"></form>
<form id="backupForm" method="POST" style="display:none;"><input type="hidden" name="action" value="backup_db"></form>

<script>
function resetSettings() {
    if (confirm('⚠️ Yakin ingin mereset semua pengaturan ke default?\n\nPengaturan yang sudah diubah akan hilang.')) {
        document.getElementById('resetForm').submit();
    }
}
function backupDatabase() {
    if (confirm('💾 Backup database sekarang?\n\nFile backup akan disimpan di folder backup/')) {
        document.getElementById('backupForm').submit();
    }
}
function refreshLog() {
    const container = document.getElementById('logContainer');
    container.innerHTML = '<div style="text-align:center;color:#999;padding:1rem;">🔄 Memuat ulang...</div>';
    fetch('../api/get_logs.php').then(r=>r.json()).then(data=>{
        if (data.success && data.logs.length > 0) {
            container.innerHTML = data.logs.map(log => '<div class="log-item">' + log + '</div>').join('');
        } else {
            container.innerHTML = '<div class="log-empty">📭 Belum ada aktivitas</div>';
        }
    }).catch(()=>{ container.innerHTML = '<div class="log-empty">❌ Gagal memuat log</div>'; });
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.setting-right input, .setting-right select, .setting-right textarea').forEach(input => {
        input.addEventListener('change', function() {
            const item = this.closest('.setting-item');
            if (item) {
                const label = item.querySelector('.setting-left .label');
                if (label) { label.style.color = '#e94560'; setTimeout(() => { label.style.color = ''; }, 1500); }
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>