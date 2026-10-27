<?php
// Product list API

require_once __DIR__ . '/bootstrap.php';

$stmt = $pdo->query("
    SELECT id, title, platform, format, sku, stock, price
    FROM products
    ORDER BY platform, title
");
$products = $stmt->fetchAll();

// Send numbers as numbers, not strings
foreach ($products as &$p) {
    $p['id']    = (int)$p['id'];
    $p['stock'] = (int)$p['stock'];
    $p['price'] = (float)$p['price'];
}
unset($p);

echo json_encode($products, JSON_UNESCAPED_UNICODE);
