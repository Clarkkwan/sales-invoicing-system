<?php
// Customers API: list, save and delete

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
    }
    echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
    exit;
}

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

$action = $body['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'save') {
    $id               = (int)($body['id'] ?? 0);
    $name             = trim((string)($body['name']             ?? ''));
    $email            = trim((string)($body['email']            ?? ''));
    $phone            = trim((string)($body['phone']            ?? ''));
    $billing_address  = trim((string)($body['billing_address']  ?? ''));
    $shipping_address = trim((string)($body['shipping_address'] ?? ''));

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }

    if ($id > 0) {
        $pdo->prepare("
            UPDATE customers SET name = ?, email = ?, phone = ?, billing_address = ?, shipping_address = ?
            WHERE id = ?
        ")->execute([$name, $email, $phone, $billing_address, $shipping_address, $id]);
    } else {
        $pdo->prepare("
            INSERT INTO customers (name, email, phone, billing_address, shipping_address)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$name, $email, $phone, $billing_address, $shipping_address]);
        $id = (int)$pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
