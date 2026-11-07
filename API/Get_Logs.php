<?php
// api/get_logs.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$logFile = __DIR__ . '/../logs/activity.log';
$logs = [];

if (file_exists($logFile)) {
    $fileLogs = file($logFile);
    $logs = array_reverse(array_slice($fileLogs, 0, 20));
    $logs = array_map('trim', $logs);
    $logs = array_filter($logs);
}

echo json_encode([
    'success' => true,
    'logs' => array_values($logs)
]);
?>