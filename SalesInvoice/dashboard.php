<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

// Metrics
$totalProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSales     = (int)$pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$totalRevenue   = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM sales")->fetchColumn();
$lowStockCount  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock < 5")->fetchColumn();
$outStockCount  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

// This month
$monthRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) FROM sales
    WHERE MONTH(date_created) = MONTH(NOW()) AND YEAR(date_created) = YEAR(NOW())
")->fetchColumn();
$monthSales = (int)$pdo->query("
    SELECT COUNT(*) FROM sales
    WHERE MONTH(date_created) = MONTH(NOW()) AND YEAR(date_created) = YEAR(NOW())
")->fetchColumn();

// Recent 8 sales
$stmt = $pdo->query("
    SELECT s.invoice_no, s.client_name, s.client_type, s.total_amount, s.date_created,
           COUNT(si.id) AS item_count
    FROM sales s
    LEFT JOIN sale_items si ON si.sale_id = s.id
    GROUP BY s.id
    ORDER BY s.date_created DESC
    LIMIT 8
");
$recentSales = $stmt->fetchAll();

// Low stock (1 to 4 left)
$lowStockItems = $pdo->query("
    SELECT title, platform, sku, stock
    FROM products
    WHERE stock > 0 AND stock < 5
    ORDER BY stock ASC
    LIMIT 6
")->fetchAll();

// Top 5 best sellers
$topProducts = $pdo->query("
    SELECT p.title, p.platform, SUM(si.quantity) AS total_sold
    FROM sale_items si
    JOIN products p ON p.id = si.product_id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

$fmtPHP = fn($n) => '₱' . number_format((float)$n, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #eef2f7;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --surface-3: #f1f5f9;
    --accent: #2563eb;
    --accent-light: #eff6ff;
    --border: #e2e8f0;
    --border-med: #cbd5e1;
    --text: #0f172a;
    --text-3: #64748b;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --heat-bg: #fef2f2;
    --ok: #16a34a;
    --ok-bg: #f0fdf4;
    --warn: #d97706;
    --warn-bg: #fffbeb;
    --purple: #7c3aed;
    --purple-bg: #f5f3ff;
    --sh-xs: 0 1px 2px rgba(15,23,42,.05);
    --sh-sm: 0 1px 3px rgba(15,23,42,.08);
    --sh-md: 0 4px 6px -1px rgba(15,23,42,.07);
    --sh-lg: 0 10px 15px -3px rgba(15,23,42,.08);
}

*, *::before, *::after {
    box-sizing: border-box;
    border-radius: 0 !important;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    background: var(--bg);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    overflow: hidden;
}

/* App shell */
#app {
    display: grid;
    grid-template-columns: 230px 1fr;
    height: 100vh;
    overflow: hidden;
}

/* Navigation */
#nav-panel {
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    min-height: 0;
    box-shadow: 2px 0 8px rgba(15,23,42,.06);
    z-index: 10;
}

#nav-panel::-webkit-scrollbar {
    width: 3px;
}

#nav-panel::-webkit-scrollbar-thumb {
    background: var(--border-med);
    border-radius: 3px;
}

.brand {
    padding: 20px 18px 16px;
    border-bottom: 1px solid var(--border);
}

.brand-gg {
    font-family: 'Roboto Mono', monospace;
    font-size: 19px;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: 2px;
    line-height: 1;
}

.brand-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--text);
    margin-top: 3px;
}

.nav-section-label {
    padding: 14px 18px 5px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--text-lo);
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 18px;
    cursor: pointer;
    border-left: 3px solid transparent;
    transition: background 0.18s ease, color 0.18s ease, border-left-color 0.18s ease;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-3);
    text-decoration: none;
    user-select: none;
}

.nav-item:hover {
    background: var(--surface-2);
    color: var(--text);
    border-left-color: var(--border-med);
}

.nav-item.active {
    background: var(--accent-light);
    color: var(--accent);
    border-left-color: var(--accent);
    font-weight: 600;
}

.nav-item svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.nav-sub {
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.32s cubic-bezier(0.4,0,0.2,1);
    background: var(--surface-2);
}

.nav-sub.open {
    max-height: 400px;
    border-bottom: 1px solid var(--border);
}

.nav-sub-item {
    display: flex;
    align-items: center;
    padding: 6px 18px 6px 44px;
    position: relative;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-3);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.18s ease, color 0.18s ease, border-left-color 0.18s ease;
    border-left: 3px solid transparent;
    user-select: none;
}

.nav-sub-item::before {
    content: '';
    width: 5px;
    height: 5px;
    background: var(--border-med);
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    transition: background 0.18s ease;
}

.nav-sub-item:hover {
    background: var(--accent-light);
    color: var(--text);
    border-left-color: var(--border-med);
}

.nav-sub-item:hover::before {
    background: var(--accent);
}

.nav-chevron {
    margin-left: auto;
    width: 12px;
    height: 12px;
    flex-shrink: 0;
    transition: transform 0.30s cubic-bezier(0.4,0,0.2,1);
    color: var(--text-lo);
}

.nav-item.expanded .nav-chevron {
    transform: rotate(180deg);
}

.nav-footer {
    padding: 12px 18px;
    margin-top: auto;
    border-top: 1px solid var(--border);
}

/* Main content */
#main-panel {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.page-toolbar {
    flex-shrink: 0;
    padding: 14px 24px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--sh-sm);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
}

.page-sub {
    font-size: 11px;
    color: var(--text-3);
    margin-top: 1px;
}

/* Dashboard body */
.dash-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

.dash-body::-webkit-scrollbar {
    width: 6px;
}

.dash-body::-webkit-scrollbar-thumb {
    background: var(--border-med);
}

/* Stat cards */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 16px 18px;
    box-shadow: var(--sh-xs);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    box-shadow: var(--sh-md);
    transform: translateY(-1px);
}

.stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.stat-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid;
    flex-shrink: 0;
}

.stat-icon svg {
    width: 16px;
    height: 16px;
}

.si-blue {
    color: var(--accent);
    border-color: #bfdbfe;
    background: var(--accent-light);
}

.si-green {
    color: var(--ok);
    border-color: #bbf7d0;
    background: var(--ok-bg);
}

.si-red {
    color: var(--heat);
    border-color: #fecaca;
    background: var(--heat-bg);
}

.si-purple {
    color: var(--purple);
    border-color: #ddd6fe;
    background: var(--purple-bg);
}

.si-slate {
    color: #475569;
    border-color: #e2e8f0;
    background: var(--surface-2);
}

.stat-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
    margin-top: 10px;
    line-height: 1;
}

.stat-lbl {
    font-size: 11px;
    color: var(--text-3);
    margin-top: 4px;
    font-weight: 500;
}

.stat-sub {
    font-size: 10px;
    color: var(--text-lo);
    margin-top: 2px;
    font-family: 'Roboto Mono', monospace;
}

.stat-heat {
    color: var(--heat);
}

.stat-card-accent-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.bar-blue {
    background: var(--accent);
}

.bar-green {
    background: var(--ok);
}

.bar-red {
    background: var(--heat);
}

.bar-purple {
    background: var(--purple);
}

/* Grid layout */
.dash-row {
    display: grid;
    gap: 14px;
    margin-bottom: 14px;
}

.dash-row-3col {
    grid-template-columns: 2fr 1fr;
}

/* Panels */
.panel {
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--sh-xs);
    overflow: hidden;
}

.panel-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 7px;
}

.panel-title::before {
    content: '';
    width: 3px;
    height: 14px;
    background: var(--accent);
    flex-shrink: 0;
}

.panel-count {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    color: var(--text-3);
    background: var(--surface-3);
    border: 1px solid var(--border);
    padding: 1px 8px;
}

/* Sales table */
.mini-table {
    width: 100%;
    border-collapse: collapse;
}

.mini-table th {
    background: var(--surface-3);
    padding: 7px 14px;
    text-align: left;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-lo);
    font-weight: 700;
    border-bottom: 1px solid var(--border);
}

.mini-table td {
    padding: 9px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
}

.mini-table tbody tr:hover td {
    background: var(--accent-light);
}

.mini-table tbody tr:last-child td {
    border-bottom: none;
}

.inv-no {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
}

.t-amount {
    font-family: 'Roboto Mono', monospace;
    font-size: 12px;
    font-weight: 700;
    color: var(--text);
}

.badge-individual {
    display: inline-block;
    padding: 1px 6px;
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    border: 1px solid;
    color: #475569;
    border-color: #e2e8f0;
    background: #f8fafc;
}

.badge-corporate {
    display: inline-block;
    padding: 1px 6px;
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    border: 1px solid;
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
}

/* Low stock items */
.low-stock-item {
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.low-stock-item:last-child {
    border-bottom: none;
}

.lsi-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
}

.lsi-sku {
    font-family: 'Roboto Mono', monospace;
    font-size: 10px;
    color: var(--text-lo);
    margin-top: 1px;
}

.lsi-stock {
    font-family: 'Roboto Mono', monospace;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border: 1px solid;
    color: var(--heat);
    border-color: #fecaca;
    background: var(--heat-bg);
}

/* Top products */
.top-prod-row {
    padding: 9px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
}

.top-prod-row:last-child {
    border-bottom: none;
}

.tp-rank {
    font-family: 'Roboto Mono', monospace;
    font-size: 10px;
    font-weight: 700;
    color: var(--text-lo);
    width: 20px;
    flex-shrink: 0;
    text-align: center;
}

.tp-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
    flex: 1;
}

.tp-sold {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
    white-space: nowrap;
}

/* Empty state */
.empty-mini {
    padding: 28px 16px;
    text-align: center;
    color: var(--text-lo);
    font-size: 12px;
}

/* Month cards row */
.month-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}

.month-card {
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--sh-xs);
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
}

.month-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-3);
    margin-bottom: 6px;
}

.month-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 26px;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
}

.month-sub {
    font-size: 10px;
    color: var(--text-lo);
    margin-top: 4px;
}

.month-accent-bar {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--accent);
}
</style>
</head>
<body>
<div id="app">

<!-- Navigation -->
<nav id="nav-panel">
    <div class="brand">
        <div class="brand-gg">SI</div>
        <div class="brand-name">Sales &amp; Invoicing System</div>
    </div>

    <div class="nav-section-label">Main Menu</div>

    <a href="dashboard.php" class="nav-item active" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="index.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Point of Sale
    </a>

    <div class="nav-item" id="nav-products-toggle" onclick="toggleProductsNav()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Products
        <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
    <div class="nav-sub" id="nav-products-sub">
        <a class="nav-sub-item" href="products.php">All</a>
        <a class="nav-sub-item" href="products.php?platform=PC">PC</a>
        <a class="nav-sub-item" href="products.php?platform=PS5">PS5</a>
        <a class="nav-sub-item" href="products.php?platform=Switch">Switch</a>
        <a class="nav-sub-item" href="products.php?platform=Xbox">Xbox</a>
        <a class="nav-sub-item" href="products.php?platform=GiftCard">Gift Cards</a>
    </div>

    <a href="sales_invoices.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sales Invoices
    </a>
    <a href="invoice-list.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Invoice Manager
    </a>

    <div class="nav-section-label">Management</div>

    <a href="customers.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Customers
    </a>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <a href="users.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        System Users
    </a>
    <?php endif; ?>

    <div class="nav-footer">
        <a href="logout.php"
           style="display:block;padding:6px 10px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-align:center;text-decoration:none;color:var(--heat);border:1px solid #fecaca;transition:all 0.12s"
           onmouseover="this.style.background='var(--heat-bg)'"
           onmouseout="this.style.background='transparent'">Logout</a>
    </div>
</nav>

<!-- Main content -->
<div id="main-panel">

    <div class="page-toolbar">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-sub">Overview — <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
        </div>
    </div>

    <div class="dash-body">

        <!-- KPI cards -->
        <div class="stat-grid">

            <div class="stat-card">
                <div class="stat-card-top">
                    <div>
                        <div class="stat-val"><?= $fmtPHP($totalRevenue) ?></div>
                        <div class="stat-lbl">Total Revenue</div>
                        <div class="stat-sub">All time</div>
                    </div>
                    <div class="stat-icon si-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/></svg>
                    </div>
                </div>
                <div class="stat-card-accent-bar bar-blue"></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <div>
                        <div class="stat-val"><?= $totalSales ?></div>
                        <div class="stat-lbl">Total Invoices</div>
                        <div class="stat-sub">All time sales</div>
                    </div>
                    <div class="stat-icon si-green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>
                <div class="stat-card-accent-bar bar-green"></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <div>
                        <div class="stat-val"><?= $totalProducts ?></div>
                        <div class="stat-lbl">Total Products</div>
                        <div class="stat-sub"><?= $outStockCount ?> out of stock</div>
                    </div>
                    <div class="stat-icon si-purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                </div>
                <div class="stat-card-accent-bar bar-purple"></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-top">
                    <div>
                        <div class="stat-val <?= $lowStockCount > 0 ? 'stat-heat' : '' ?>"><?= $lowStockCount ?></div>
                        <div class="stat-lbl">Low Stock Items</div>
                        <div class="stat-sub">Below 5 units</div>
                    </div>
                    <div class="stat-icon <?= $lowStockCount > 0 ? 'si-red' : 'si-slate' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                </div>
                <div class="stat-card-accent-bar <?= $lowStockCount > 0 ? 'bar-red' : '' ?>" style="<?= $lowStockCount === 0 ? 'background:var(--border)' : '' ?>"></div>
            </div>

        </div>

        <!-- This month -->
        <div class="month-row">
            <div class="month-card">
                <div class="month-accent-bar"></div>
                <div class="month-label">This Month — Revenue</div>
                <div class="month-val"><?= $fmtPHP($monthRevenue) ?></div>
                <div class="month-sub"><?= date('F Y') ?></div>
            </div>
            <div class="month-card">
                <div class="month-accent-bar" style="background:var(--ok)"></div>
                <div class="month-label">This Month — Invoices</div>
                <div class="month-val"><?= $monthSales ?></div>
                <div class="month-sub"><?= $monthSales === 1 ? 'sale' : 'sales' ?> this month</div>
            </div>
        </div>

        <!-- Recent sales + low stock -->
        <div class="dash-row dash-row-3col">

            <!-- Recent sales -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">Recent Invoices</span>
                    <span class="panel-count"><?= count($recentSales) ?></span>
                </div>
                <?php if (empty($recentSales)): ?>
                <div class="empty-mini">No sales yet</div>
                <?php else: ?>
                <table class="mini-table">
                    <thead><tr>
                        <th>Invoice No</th><th>Client</th><th>Type</th>
                        <th style="text-align:right">Total</th><th>Date</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentSales as $s): ?>
                    <tr style="cursor:pointer" onclick="window.open('invoice.php?no=<?= urlencode($s['invoice_no']) ?>','_blank')">
                        <td><span class="inv-no"><?= htmlspecialchars($s['invoice_no']) ?></span></td>
                        <td style="font-weight:600;color:var(--text);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($s['client_name']) ?></td>
                        <td><span class="badge-<?= htmlspecialchars($s['client_type']) ?>"><?= htmlspecialchars($s['client_type']) ?></span></td>
                        <td style="text-align:right"><span class="t-amount"><?= $fmtPHP($s['total_amount']) ?></span></td>
                        <td style="font-size:10px;color:var(--text-3)"><?= date('M d', strtotime($s['date_created'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:10px 14px;border-top:1px solid var(--border);background:var(--surface-2)">
                    <a href="sales_invoices.php" style="font-size:11px;font-weight:600;color:var(--accent);text-decoration:none">View all invoices →</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right column -->
            <div style="display:flex;flex-direction:column;gap:14px">

                <!-- Low stock -->
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title" style="color:<?= $lowStockCount > 0 ? 'var(--heat)' : 'var(--text)' ?>">Low Stock Alert</span>
                        <?php if ($lowStockCount > 0): ?><span class="panel-count" style="color:var(--heat);border-color:#fecaca;background:var(--heat-bg)"><?= $lowStockCount ?></span><?php endif; ?>
                    </div>
                    <?php if (empty($lowStockItems)): ?>
                    <div class="empty-mini" style="color:var(--ok)">All items well stocked</div>
                    <?php else: ?>
                    <?php foreach ($lowStockItems as $item): ?>
                    <div class="low-stock-item">
                        <div>
                            <div class="lsi-title"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="lsi-sku"><?= htmlspecialchars($item['platform']) ?> · <?= htmlspecialchars($item['sku']) ?></div>
                        </div>
                        <span class="lsi-stock"><?= (int)$item['stock'] ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div style="padding:8px 14px;border-top:1px solid var(--border);background:var(--surface-2)">
                        <a href="products.php" style="font-size:11px;font-weight:600;color:var(--accent);text-decoration:none">Manage products →</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Top products -->
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Top Selling Products</span>
                    </div>
                    <?php if (empty($topProducts)): ?>
                    <div class="empty-mini">No sales data yet</div>
                    <?php else: ?>
                    <?php foreach ($topProducts as $i => $p): ?>
                    <div class="top-prod-row">
                        <span class="tp-rank">#<?= $i+1 ?></span>
                        <div>
                            <div class="tp-title"><?= htmlspecialchars($p['title']) ?></div>
                            <div style="font-size:10px;color:var(--text-lo)"><?= htmlspecialchars($p['platform']) ?></div>
                        </div>
                        <span class="tp-sold"><?= (int)$p['total_sold'] ?> sold</span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div><!-- /dash-body -->
</div>

</div>

<script>
function toggleProductsNav() {
    const toggle = document.getElementById('nav-products-toggle');
    const sub    = document.getElementById('nav-products-sub');
    const open   = toggle.classList.toggle('expanded');
    sub.classList.toggle('open', open);
    sub.style.maxHeight = open ? sub.scrollHeight + 'px' : '0';
}
</script>
</body>
</html>
