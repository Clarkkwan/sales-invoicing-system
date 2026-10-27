<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$invoiceNo = trim((string)($_GET['no'] ?? ''));

if (!$invoiceNo || !preg_match('/^SBC-\d{4}-\d{4}$/', $invoiceNo)) {
    http_response_code(400);
    die('<div style="font-family:monospace;padding:24px;background:#0a0a0c;color:#ef4444;border:2px solid #ef4444;margin:24px;">INVALID INVOICE NUMBER</div>');
}

$stmt = $pdo->prepare("SELECT * FROM sales WHERE invoice_no = ?");
$stmt->execute([$invoiceNo]);
$sale = $stmt->fetch();

if (!$sale) {
    http_response_code(404);
    die('<div style="font-family:monospace;padding:24px;background:#0a0a0c;color:#ef4444;border:2px solid #ef4444;margin:24px;">INVOICE NOT FOUND: ' . htmlspecialchars($invoiceNo) . '</div>');
}

$stmt = $pdo->prepare("
    SELECT si.quantity, si.unit_price, p.title, p.sku, p.platform, p.format
    FROM sale_items si
    JOIN products p ON p.id = si.product_id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale['id']]);
$items = $stmt->fetchAll();

$copies = ['ACCOUNTING', 'CUSTOMER', 'BIR', 'FILE'];
$fmtPHP = fn($n) => '₱' . number_format((float)$n, 2);
$fmtDate = date('F d, Y', strtotime($sale['date_created']));
$fmtTime = date('h:i A', strtotime($sale['date_created']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= htmlspecialchars($invoiceNo) ?> - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Roboto+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    border-radius: 0 !important;
}

:root {
    --bg: #eef2f7;
    --surface: #ffffff;
    --accent: #2563eb;
    --text: #0f172a;
    --dim: #94a3b8;
    --border: #e2e8f0;
}

/* Screen view */
body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
}

.screen-controls {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: var(--surface);
    border-bottom: 2px solid var(--accent);
    padding: 12px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 100;
    box-shadow: 0 1px 3px rgba(15,23,42,.08);
}

.sc-title {
    font-family: 'Roboto Mono', monospace;
    font-size: 14px;
    color: var(--accent);
    letter-spacing: 2px;
    font-weight: 700;
}

.sc-actions {
    display: flex;
    gap: 12px;
}

.btn-print, .btn-back {
    padding: 8px 20px;
    border: 1px solid;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: 'Inter', sans-serif;
    transition: all 0.15s;
}

.btn-print {
    border-color: var(--accent);
    color: white;
    background: var(--accent);
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-print:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 8px rgba(37,99,235,.35);
}

.btn-back {
    border-color: var(--border);
    color: #64748b;
    background: var(--surface);
}

.btn-back:hover {
    border-color: #cbd5e1;
    color: var(--text);
    background: #f8fafc;
}

.copies-wrapper {
    max-width: 820px;
    margin: 80px auto 48px;
    padding: 0 24px;
}

/* Invoice copy */
.invoice-copy {
    background: white;
    color: #111;
    margin-bottom: 32px;
    border: 1px solid #e2e8f0;
    padding: 32px 40px 40px;
    box-shadow: 0 4px 6px -1px rgba(15,23,42,.07), 0 2px 4px -2px rgba(15,23,42,.04);
}

.copy-tag {
    display: inline-block;
    padding: 3px 12px;
    border: 2px solid currentColor;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 20px;
    font-family: 'Roboto Mono', monospace;
}

.copy-tag.accounting {
    color: #1d4ed8;
}

.copy-tag.customer {
    color: #059669;
}

.copy-tag.bir {
    color: #dc2626;
}

.copy-tag.file {
    color: #7c3aed;
}

.inv-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 20px;
    border-bottom: 3px solid #111;
    margin-bottom: 20px;
}

.inv-brand-name {
    font-size: 22px;
    font-weight: 900;
    letter-spacing: 3px;
    text-transform: uppercase;
    line-height: 1;
    font-family: 'Roboto Mono', monospace;
}

.inv-brand-sub {
    font-size: 10px;
    color: #666;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 4px;
}

.inv-brand-tagline {
    font-size: 11px;
    color: #888;
    margin-top: 8px;
}

.inv-meta {
    text-align: right;
}

.inv-type {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #111;
    line-height: 1;
}

.inv-no {
    font-family: 'Roboto Mono', monospace;
    font-size: 15px;
    font-weight: 700;
    margin-top: 6px;
    color: #1d4ed8;
    letter-spacing: 2px;
}

.inv-date {
    font-size: 11px;
    color: #555;
    margin-top: 4px;
    font-family: 'Roboto Mono', monospace;
}

.inv-billed-to {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
    padding: 16px;
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
}

.billed-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #888;
    margin-bottom: 4px;
}

.billed-name {
    font-size: 15px;
    font-weight: 700;
    color: #111;
}

.billed-type {
    font-size: 11px;
    color: #555;
    margin-top: 2px;
}

/* Items table */
.inv-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    font-size: 12px;
}

.inv-table th {
    background: #111;
    color: white;
    padding: 8px 10px;
    text-align: left;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-weight: 700;
}

.inv-table th:last-child, .inv-table td:last-child {
    text-align: right;
}

.inv-table th:nth-child(3),
.inv-table td:nth-child(3),
.inv-table th:nth-child(4),
.inv-table td:nth-child(4) {
    text-align: right;
}

.inv-table td {
    padding: 9px 10px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.inv-table tr:last-child td {
    border-bottom: 2px solid #111;
}

.td-title {
    font-weight: 600;
    color: #111;
}

.td-sku {
    font-family: 'Roboto Mono', monospace;
    font-size: 10px;
    color: #888;
    margin-top: 2px;
}

.td-mono {
    font-family: 'Roboto Mono', monospace;
    font-weight: 600;
}

/* Tax breakdown */
.inv-totals {
    display: flex;
    justify-content: flex-end;
    margin-top: 0;
}

.totals-table {
    width: 280px;
    border-left: 3px solid #111;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 12px;
    font-size: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.totals-row .lbl {
    color: #555;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 10px;
}

.totals-row .val {
    font-family: 'Roboto Mono', monospace;
    font-weight: 600;
    color: #111;
}

.totals-grand {
    display: flex;
    justify-content: space-between;
    padding: 10px 12px;
    background: #111;
    color: white;
}

.totals-grand .lbl {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.totals-grand .val {
    font-family: 'Roboto Mono', monospace;
    font-size: 16px;
    font-weight: 700;
}

.ewt-note {
    padding: 6px 12px;
    background: #fffbeb;
    border: 1px solid #f59e0b;
    border-top: none;
    font-size: 10px;
    color: #92400e;
    font-family: 'Roboto Mono', monospace;
}

/* Signatures */
.inv-signatures {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 24px;
    margin-top: 48px;
    padding-top: 0;
}

.sig-block {
    text-align: center;
}

.sig-line {
    border-top: 2px solid #111;
    padding-top: 6px;
    font-size: 10px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-top: 32px;
}

.inv-footer {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
    font-size: 10px;
    color: #888;
    text-align: center;
    line-height: 1.6;
}

/* Print: A4 page, 4 copies */
@media print {
    @page {
        size: A4;
        margin: 12mm 15mm;
    }

    body {
        background: white !important;
    }

    .screen-controls {
        display: none !important;
    }

    .copies-wrapper {
        margin: 0;
        padding: 0;
        max-width: none;
    }

    .invoice-copy {
        border: none;
        padding: 0;
        margin: 0;
        page-break-after: always;
    }

    .invoice-copy:last-child {
        page-break-after: avoid;
    }
}
</style>
</head>
<body>

<!-- Screen controls -->
<div class="screen-controls no-print">
    <span class="sc-title">// <?= htmlspecialchars($invoiceNo) ?></span>
    <div class="sc-actions">
        <a href="index.php" class="btn-back">← Back to POS</a>
        <button class="btn-print" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print All 4 Copies
        </button>
    </div>
</div>

<div class="copies-wrapper">
<?php foreach ($copies as $idx => $copyName): ?>
<div class="invoice-copy">

    <!-- Copy tag -->
    <div class="copy-tag <?= strtolower($copyName) ?>"><?= $copyName ?> COPY</div>

    <!-- Header -->
    <div class="inv-header">
        <div>
            <div class="inv-brand-name">Sales &amp; Invoicing System</div>
            <div class="inv-brand-sub">Games &amp; Entertainment Distribution</div>
            <div class="inv-brand-tagline">VAT Registered · TIN: 000-000-000-000</div>
        </div>
        <div class="inv-meta">
            <div class="inv-type">Service Invoice</div>
            <div class="inv-no"><?= htmlspecialchars($invoiceNo) ?></div>
            <div class="inv-date"><?= $fmtDate ?> · <?= $fmtTime ?></div>
        </div>
    </div>

    <!-- Billed to -->
    <div class="inv-billed-to">
        <div>
            <div class="billed-label">Billed To</div>
            <div class="billed-name"><?= htmlspecialchars($sale['client_name']) ?></div>
            <div class="billed-type"><?= ucfirst($sale['client_type']) ?> Client</div>
        </div>
        <div>
            <div class="billed-label">Payment Terms</div>
            <div class="billed-name">Cash / Upon Delivery</div>
            <?php if ($sale['ewt_rate'] > 0): ?>
            <div class="billed-type">EWT Rate: <?= number_format($sale['ewt_rate'], 0) ?>% (BIR Form 2307)</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items table -->
    <table class="inv-table">
        <thead>
            <tr>
                <th style="width:40px">QTY</th>
                <th>Description</th>
                <th style="width:80px">Platform</th>
                <th style="width:100px">Unit Price</th>
                <th style="width:110px">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="td-mono" style="text-align:center"><?= (int)$item['quantity'] ?></td>
                <td>
                    <div class="td-title"><?= htmlspecialchars($item['title']) ?></div>
                    <div class="td-sku"><?= htmlspecialchars($item['sku']) ?> · <?= htmlspecialchars($item['format']) ?></div>
                </td>
                <td style="font-size:11px;color:#555;"><?= htmlspecialchars($item['platform']) ?></td>
                <td class="td-mono"><?= $fmtPHP($item['unit_price']) ?></td>
                <td class="td-mono"><?= $fmtPHP($item['unit_price'] * $item['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="inv-totals">
        <div class="totals-table">
            <div class="totals-row">
                <span class="lbl">Subtotal (Gross)</span>
                <span class="val"><?= $fmtPHP($sale['subtotal']) ?></span>
            </div>
            <div class="totals-row">
                <span class="lbl">VAT (12%) Exclusive</span>
                <span class="val"><?= $fmtPHP($sale['vat_amount']) ?></span>
            </div>
            <?php if ($sale['ewt_amount'] > 0): ?>
            <div class="totals-row">
                <span class="lbl">EWT (<?= number_format($sale['ewt_rate'],0) ?>%) Deduction</span>
                <span class="val" style="color:#dc2626;">-<?= $fmtPHP($sale['ewt_amount']) ?></span>
            </div>
            <?php endif; ?>
            <div class="totals-grand">
                <span class="lbl">Total Amount Due</span>
                <span class="val"><?= $fmtPHP($sale['total_amount']) ?></span>
            </div>
            <?php if ($sale['ewt_amount'] > 0): ?>
            <div class="ewt-note">EWT Withheld by Customer · BIR Form 2307 Required</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Signatures -->
    <div class="inv-signatures">
        <div class="sig-block">
            <div class="sig-line">Prepared By / Cashier</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Received By / Customer</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Authorized Signature</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="inv-footer">
        Sales &amp; Invoicing System · This is a system-generated invoice. · <?= $fmtDate ?>
        <?php if ($sale['client_type'] === 'corporate'): ?>
        · Subject to EWT per BIR regulations.
        <?php endif; ?>
    </div>

</div>
<?php endforeach; ?>
</div>

</body>
</html>
