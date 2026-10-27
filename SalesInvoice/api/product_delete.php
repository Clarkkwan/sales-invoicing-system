<?php
// Delete a product (blocked if it has sales history)

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$id   = isset($body['id']) ? (int)$body['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

$check = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
$check->execute([$id]);
if ((int)$check->fetchColumn() > 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Cannot delete: product has sales history. Set stock to 0 instead.',
    ]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

echo json_encode(['success' => true]);
