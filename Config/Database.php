<?php
// config/database.php
$host = 'localhost';
$dbname = 'kasir_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk log aktivitas
function logActivity($message) {
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}

// ===== FUNGSI getSettings() =====
function getSettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    } catch (PDOException $e) {
        // Jika tabel settings belum ada, return default
        return [
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
    }
}

// Fungsi untuk mendapatkan setting tertentu
function getSetting($pdo, $key, $default = '') {
    $settings = getSettings($pdo);
    return $settings[$key] ?? $default;
}
?>