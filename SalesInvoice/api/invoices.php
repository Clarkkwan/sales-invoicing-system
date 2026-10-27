<?php
// Invoice Manager API: list, view, save, mark paid/open and delete

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id   = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id=?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            exit;
        }
        $invoice['id'] = (int)$invoice['id'];

        $stmt  = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id");
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['id']         = (int)$item['id'];
            $item['invoice_id'] = (int)$item['invoice_id'];
        }
        $invoice['items'] = $items;

        echo json_encode($invoice, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->query("
        SELECT i.*, COUNT(ii.id) AS item_count
        FROM invoices i
        LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
        GROUP BY i.id
        ORDER BY i.created_at DESC
        LIMIT 300
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id']         = (int)$r['id'];
        $r['item_count'] = (int)$r['item_count'];
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

if ($action === 'status') {
    $id     = (int)($body['id'] ?? 0);
    $status = in_array($body['status'] ?? '', ['open', 'paid']) ? $body['status'] : 'open';
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$status, $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }
    $pdo->prepare("DELETE FROM invoices WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'save') {
    $id               = (int)($body['id'] ?? 0);
    $invoice_type     = in_array($body['invoice_type'] ?? '', ['invoice', 'quote', 'receipt']) ? $body['invoice_type'] : 'invoice';
    $status           = in_array($body['status'] ?? '', ['open', 'paid']) ? $body['status'] : 'open';
    $customer_id      = !empty($body['customer_id']) ? (int)$body['customer_id'] : null;
    $customer_name    = trim((string)($body['customer_name']    ?? ''));
    $billing_address  = trim((string)($body['billing_address']  ?? ''));
    $shipping_address = trim((string)($body['shipping_address'] ?? ''));
    $invoice_date     = trim((string)($body['invoice_date']     ?? ''));
    $due_date         = trim((string)($body['due_date']         ?? '')) ?: null;
    $notes            = trim((string)($body['notes']            ?? ''));
    $custom_message   = trim((string)($body['custom_message']   ?? ''));
    $shipping_cost    = max(0, (float)($body['shipping_cost']   ?? 0));
    $items            = is_array($body['items'] ?? null) ? array_filter($body['items'], 'is_array') : [];

    // Real dates only (rejects things like 2026-02-30)
    $isDate = fn($d) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
        && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4));

    if (empty($customer_name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        exit;
    }
    if (empty($invoice_date)) {
        echo json_encode(['success' => false, 'error' => 'Invoice date is required']);
        exit;
    }
    if (!$isDate($invoice_date) || ($due_date !== null && !$isDate($due_date))) {
        echo json_encode(['success' => false, 'error' => 'Invalid date']);
        exit;
    }
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'At least one line item is required']);
        exit;
    }

    // Calculate totals
    $subtotal = 0;
    $cleanItems = [];
    foreach ($items as $item) {
        $qty        = max(0, (float)($item['quantity']   ?? 1));
        $unitPrice  = max(0, (float)($item['unit_price'] ?? 0));
        $discount   = max(0, (float)($item['discount']   ?? 0));
        $lineTotal  = ($qty * $unitPrice) - $discount;
        $subtotal  += $lineTotal;
        $cleanItems[] = [
            'product_id' => !empty($item['product_id']) ? (int)$item['product_id'] : null,
            'item_name'  => trim((string)($item['item_name'] ?? 'Item')),
            'quantity'   => $qty,
            'unit_price' => $unitPrice,
            'discount'   => $discount,
            'subtotal'   => round($lineTotal, 2),
        ];
    }

    // Discount comes off before VAT, shipping is added after
    $discount_total = max(0, (float)($body['discount'] ?? 0));
    $subtotal       = max(0, $subtotal - $discount_total);
    $vat_amount     = round($subtotal * 0.12, 2);
    $total_amount   = round($subtotal + $vat_amount + $shipping_cost, 2);

    try {
        $pdo->beginTransaction();

        if ($id > 0) {
            $pdo->prepare("
                UPDATE invoices
                SET invoice_type = ?, status = ?, customer_id = ?, customer_name = ?,
                    billing_address = ?, shipping_address = ?, invoice_date = ?, due_date = ?,
                    subtotal = ?, discount = ?, shipping_cost = ?, vat_amount = ?, total_amount = ?,
                    notes = ?, custom_message = ?
                WHERE id = ?
            ")->execute([
                $invoice_type, $status, $customer_id, $customer_name,
                $billing_address, $shipping_address, $invoice_date, $due_date,
                round($subtotal, 2), $discount_total, $shipping_cost, $vat_amount, $total_amount,
                $notes, $custom_message, $id,
            ]);
            // Replace all line items on save
            $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id=?")->execute([$id]);
        } else {
            // Next invoice number (SBC-YYYY-NNNN)
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT invoice_no FROM invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt->execute(["SBC-{$year}-%"]);
            $last = $stmt->fetchColumn();
            $seq  = 1;
            if ($last && preg_match('/(\d{4})$/', $last, $m)) {
                $seq = (int)$m[1] + 1;
            }
            $invoice_no = 'SBC-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $pdo->prepare("
                INSERT INTO invoices (
                    invoice_no, invoice_type, status, customer_id, customer_name,
                    billing_address, shipping_address, invoice_date, due_date,
                    subtotal, discount, shipping_cost, vat_amount, total_amount,
                    notes, custom_message
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $invoice_no, $invoice_type, $status, $customer_id, $customer_name,
                $billing_address, $shipping_address, $invoice_date, $due_date,
                round($subtotal, 2), $discount_total, $shipping_cost, $vat_amount, $total_amount,
                $notes, $custom_message,
            ]);
            $id = (int)$pdo->lastInsertId();
        }

        $insertItem = $pdo->prepare("
            INSERT INTO invoice_items (invoice_id, product_id, item_name, quantity, unit_price, discount, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($cleanItems as $item) {
            $insertItem->execute([
                $id, $item['product_id'], $item['item_name'], $item['quantity'],
                $item['unit_price'], $item['discount'], $item['subtotal'],
            ]);
        }

        $pdo->commit();

        $invNo = $pdo->prepare("SELECT invoice_no FROM invoices WHERE id=?");
        $invNo->execute([$id]);
        echo json_encode(['success' => true, 'id' => $id, 'invoice_no' => $invNo->fetchColumn()]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => publicError('Transaction failed', $e)]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
