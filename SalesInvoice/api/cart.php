<?php
// Saves the POS cart in the session (finalize.php re-checks prices and stock)

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

$action = $body['action'] ?? 'set';

switch ($action) {
    case 'set':
        $cart = is_array($body['cart'] ?? null) ? $body['cart'] : [];
        // Store raw values (escaped when displayed)
        $_SESSION['cart'] = array_map(function ($item) {
            return [
                'product_id' => (int)($item['product_id'] ?? 0),
                'title'      => (string)($item['title'] ?? ''),
                'sku'        => (string)($item['sku'] ?? ''),
                'platform'   => (string)($item['platform'] ?? ''),
                'format'     => (string)($item['format'] ?? ''),
                'price'      => round((float)($item['price'] ?? 0), 2),
                'stock'      => (int)($item['stock'] ?? 0),
                'quantity'   => max(1, (int)($item['quantity'] ?? 1)),
            ];
        }, array_values(array_filter($cart, 'is_array')));
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}

echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
