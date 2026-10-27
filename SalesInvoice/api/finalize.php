<?php
// Finalize a POS sale

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Check input
$clientName = trim((string)($body['client_name'] ?? ''));
$clientType = in_array($body['client_type'] ?? '', ['individual', 'corporate'])
    ? $body['client_type']
    : 'individual';
$ewtRate    = in_array((float)($body['ewt_rate'] ?? 0), [0, 1, 2]) ? (float)$body['ewt_rate'] : 0;
$cart       = is_array($body['cart'] ?? null) ? $body['cart'] : [];

if (empty($clientName)) {
    echo json_encode(['success' => false, 'error' => 'Client name is required']);
    exit;
}

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// Reject bad quantities and merge duplicate lines
$quantities = [];
foreach ($cart as $item) {
    $pid = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['quantity']   ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid cart item']);
        exit;
    }
    $quantities[$pid] = ($quantities[$pid] ?? 0) + $qty;
}

// Check products and stock
$productIds = array_keys($quantities);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$stmt = $pdo->prepare("SELECT id, stock, price FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$dbProducts = [];
foreach ($stmt->fetchAll() as $row) {
    $dbProducts[(int)$row['id']] = $row;
}

foreach ($quantities as $pid => $qty) {
    if (!isset($dbProducts[$pid])) {
        echo json_encode(['success' => false, 'error' => "Product ID {$pid} not found"]);
        exit;
    }
    if ($dbProducts[$pid]['stock'] < $qty) {
        echo json_encode(['success' => false, 'error' => "Insufficient stock for product ID {$pid}"]);
        exit;
    }
}

// Calculate totals
$subtotal = 0;
foreach ($quantities as $pid => $qty) {
    // Always use the database price, not the browser's
    $subtotal += (float)$dbProducts[$pid]['price'] * $qty;
}

$vatAmount   = round($subtotal * 0.12, 2);              // VAT exclusive, 12%
$ewtAmount   = round($subtotal * ($ewtRate / 100), 2);  // EWT 0/1/2% (withheld by the client)
$totalAmount = round(($subtotal + $vatAmount) - $ewtAmount, 2);

$invoiceNo = getNextInvoiceNo($pdo);

// Save the sale and update stock in one transaction
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO sales (
            invoice_no, client_name, client_type, ewt_rate,
            subtotal, vat_amount, ewt_amount, total_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $invoiceNo, $clientName, $clientType, $ewtRate,
        $subtotal, $vatAmount, $ewtAmount, $totalAmount,
    ]);
    $saleId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    // Stock can't go below zero, even if another sale took it first
    $deductStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    foreach ($quantities as $pid => $qty) {
        $unitPrice = (float)$dbProducts[$pid]['price'];

        $insertItem->execute([$saleId, $pid, $qty, $unitPrice]);
        $deductStock->execute([$qty, $pid, $qty]);
        if ($deductStock->rowCount() === 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => "Insufficient stock for product ID {$pid}"]);
            exit;
        }
    }

    $pdo->commit();

    $_SESSION['cart'] = [];

    echo json_encode([
        'success'    => true,
        'invoice_no' => $invoiceNo,
        'sale_id'    => $saleId,
        'total'      => $totalAmount,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => publicError('Transaction failed', $e)]);
}
