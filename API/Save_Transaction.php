<?php
// api/save_transaction.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak valid']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Generate invoice number
    $date = date('Ymd');
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = CURDATE()");
    $count = $stmt->fetch()['count'] + 1;
    $invoice = 'INV' . $date . str_pad($count, 4, '0', STR_PAD_LEFT);

    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, customer_name, total_amount, payment_amount, change_amount, invoice_number, transaction_date)
        VALUES (?, ?, ?, ?, ?, ?, CURDATE())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $data['customer_name'] ?? 'Umum',
        $data['total'],
        $data['payment'],
        $data['change'],
        $invoice
    ]);
    $transactionId = $pdo->lastInsertId();

    // Insert details and update stock
    $items = [];
    foreach ($data['items'] as $item) {
        $stmt = $pdo->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $subtotal = $item['price'] * $item['qty'];
        $stmt->execute([$transactionId, $item['id'], $item['qty'], $item['price'], $subtotal]);

        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['qty'], $item['id']]);
        
        $items[] = [
            'name' => $item['name'],
            'qty' => $item['qty'],
            'price' => $item['price'],
            'subtotal' => $subtotal
        ];
    }

    $pdo->commit();
    
    // Log activity
    logActivity('Transaksi berhasil: ' . $invoice . ' - Rp ' . number_format($data['total'], 0, ',', '.'));

    echo json_encode([
        'success' => true,
        'invoice' => $invoice,
        'transaction_id' => $transactionId,
        'items' => $items,
        'total' => $data['total'],
        'payment' => $data['payment'],
        'change' => $data['change'],
        'customer_name' => $data['customer_name'] ?? 'Umum',
        'cashier' => $_SESSION['name'] ?? 'Kasir',
        'date' => date('d/m/Y H:i:s')
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>