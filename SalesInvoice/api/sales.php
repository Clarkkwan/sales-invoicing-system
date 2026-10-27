<?php
// Sales history API (latest sales, max 200)

require_once __DIR__ . '/bootstrap.php';

$limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));

$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.invoice_no,
        s.client_name,
        s.client_type,
        s.ewt_rate,
        s.subtotal,
        s.vat_amount,
        s.ewt_amount,
        s.total_amount,
        s.date_created,
        COUNT(si.id) AS item_count
    FROM sales s
    LEFT JOIN sale_items si ON si.sale_id = s.id
    GROUP BY s.id
    ORDER BY s.date_created DESC
    LIMIT ?
");
$stmt->execute([$limit]);
$sales = $stmt->fetchAll();

foreach ($sales as &$s) {
    $s['id']           = (int)$s['id'];
    $s['ewt_rate']     = (float)$s['ewt_rate'];
    $s['subtotal']     = (float)$s['subtotal'];
    $s['vat_amount']   = (float)$s['vat_amount'];
    $s['ewt_amount']   = (float)$s['ewt_amount'];
    $s['total_amount'] = (float)$s['total_amount'];
    $s['item_count']   = (int)$s['item_count'];
}
unset($s);

echo json_encode($sales, JSON_UNESCAPED_UNICODE);
