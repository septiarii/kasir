<?php
// api/get_transaction.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT t.*, u.name as cashier_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT td.*, p.name as product_name FROM transaction_details td JOIN products p ON td.product_id = p.id WHERE td.transaction_id = ?");
    $stmt->execute([$id]);
    $details = $stmt->fetchAll();
    
    $items = [];
    foreach ($details as $d) {
        $items[] = [
            'name' => $d['product_name'],
            'qty' => $d['quantity'],
            'price' => floatval($d['price']),
            'subtotal' => floatval($d['subtotal'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'invoice' => $transaction['invoice_number'],
        'date' => date('d/m/Y H:i:s', strtotime($transaction['created_at'])),
        'cashier' => $transaction['cashier_name'] ?? 'Kasir',
        'customer_name' => $transaction['customer_name'],
        'items' => $items,
        'total' => floatval($transaction['total_amount']),
        'payment' => floatval($transaction['payment_amount']),
        'change' => floatval($transaction['change_amount'])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>