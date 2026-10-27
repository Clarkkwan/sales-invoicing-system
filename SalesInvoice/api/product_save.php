<?php
// Add a new product, or update one when an id is sent

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

$id       = isset($body['id']) && $body['id'] ? (int)$body['id'] : null;
$title    = trim((string)($body['title']    ?? ''));
$platform = trim((string)($body['platform'] ?? ''));
$format   = trim((string)($body['format']   ?? ''));
$sku      = trim((string)($body['sku']      ?? ''));
$stock    = max(0, (int)($body['stock']     ?? 0));
$price    = round((float)($body['price']    ?? 0), 2);

$allowedPlatforms = ['PC', 'PS5', 'Switch', 'Xbox', 'GiftCard', 'Other'];
$allowedFormats   = ['Disk', 'Card', 'Digital'];

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}
if (!$platform) {
    echo json_encode(['success' => false, 'error' => 'Platform is required']);
    exit;
}
if (!in_array($platform, $allowedPlatforms)) {
    echo json_encode(['success' => false, 'error' => 'Invalid platform']);
    exit;
}
if (!in_array($format, $allowedFormats)) {
    echo json_encode(['success' => false, 'error' => 'Invalid format']);
    exit;
}
if (!$sku) {
    echo json_encode(['success' => false, 'error' => 'SKU is required']);
    exit;
}
if ($price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Price must be > 0']);
    exit;
}

// SKU must be unique (ignoring the product being edited)
$skuCheck = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
$skuCheck->execute([$sku, $id ?? 0]);
if ($skuCheck->fetch()) {
    echo json_encode(['success' => false, 'error' => "SKU '{$sku}' is already in use"]);
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE products SET title = ?, platform = ?, format = ?, sku = ?, stock = ?, price = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $platform, $format, $sku, $stock, $price, $id]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            exit;
        }
        echo json_encode(['success' => true, 'action' => 'updated', 'id' => $id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO products (title, platform, format, sku, stock, price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $platform, $format, $sku, $stock, $price]);
        echo json_encode(['success' => true, 'action' => 'created', 'id' => (int)$pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => publicError('DB error', $e)]);
}
