<?php
// Add stock to a product

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

$id  = (int)($body['id']  ?? 0);
$qty = (int)($body['qty'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}
if ($qty <= 0) {
    echo json_encode(['success' => false, 'error' => 'Quantity must be greater than 0']);
    exit;
}

$stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
$stmt->execute([$qty, $id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

$stockQuery = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
$stockQuery->execute([$id]);
$newStock = (int)$stockQuery->fetchColumn();

echo json_encode(['success' => true, 'new_stock' => $newStock]);
