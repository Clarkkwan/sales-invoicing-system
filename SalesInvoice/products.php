<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db_config.php';

$stmt = $pdo->query("
    SELECT id, title, platform, format, sku, stock, price
    FROM products
    ORDER BY FIELD(platform, 'PC', 'PS5', 'Switch', 'Xbox', 'GiftCard', 'Other'), title
");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Management - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #eef2f7;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --surface-3: #f1f5f9;
    --accent: #2563eb;
    --border: #e2e8f0;
    --border-accent: rgba(37,99,235,0.25);
    --text: #334155;
    --text-hi: #0f172a;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --ok: #16a34a;
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
    color: var(--text-hi);
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
    transition: all 0.12s;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-lo);
    text-decoration: none;
    user-select: none;
}

.nav-item:hover {
    background: var(--surface-2);
    color: var(--text-hi);
    border-left-color: var(--border);
}

.nav-item.active {
    background: rgba(37,99,235,0.08);
    color: var(--accent);
    border-left-color: var(--accent);
    font-weight: 600;
}

.nav-item svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

/* Navigation sub-menu */
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
    color: var(--text-lo);
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
    background: var(--border);
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%);
    transition: background 0.18s ease;
}

.nav-sub-item:hover {
    background: rgba(37,99,235,.06);
    color: var(--text-hi);
    border-left-color: var(--border);
}

.nav-sub-item:hover::before {
    background: var(--accent);
}

.nav-sub-item.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: rgba(37,99,235,.08);
}

.nav-sub-item.active::before {
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

/* Platform filter tabs */
.filter-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-top: 0;
}

.ftab {
    padding: 5px 14px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-lo);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 1px 2px rgba(15,23,42,.05);
}

.ftab:hover {
    background: var(--surface-2);
    border-color: var(--border-accent);
    color: var(--text-hi);
}

.ftab.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
    box-shadow: 0 1px 3px rgba(37,99,235,.3);
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
    background: var(--bg);
}

.page-toolbar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 20px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}

.page-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-hi);
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-wrap {
    position: relative;
}

.search-wrap svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 13px;
    height: 13px;
    color: var(--text-lo);
    pointer-events: none;
}

#search-products {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-hi);
    padding: 8px 10px 8px 32px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    width: 240px;
    transition: all 0.15s;
    box-shadow: 0 1px 2px rgba(15,23,42,.05);
}

#search-products:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(37,99,235,.18);
}

#search-products::placeholder {
    color: var(--text-lo);
}

.btn-add {
    padding: 8px 18px;
    background: var(--accent);
    border: none;
    color: white;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-add:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 10px rgba(37,99,235,.35);
}

/* Products table */
.table-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

.table-wrap::-webkit-scrollbar {
    width: 6px;
}

.table-wrap::-webkit-scrollbar-track {
    background: var(--bg);
}

.table-wrap::-webkit-scrollbar-thumb {
    background: #cbd5e1;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

th {
    background: var(--surface-3);
    padding: 10px 16px;
    text-align: left;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--text-lo);
    font-weight: 700;
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}

td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    background: var(--surface);
}

tbody tr {
    transition: background 0.1s;
}

tbody tr:hover td {
    background: rgba(37,99,235,0.04);
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    border: 1px solid;
}

.badge-PS5 {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
}

.badge-Switch {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fef2f2;
}

.badge-Xbox {
    color: #15803d;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.badge-PC {
    color: #4338ca;
    border-color: #c7d2fe;
    background: #eef2ff;
}

.badge-Other {
    color: #475569;
    border-color: #e2e8f0;
    background: #f8fafc;
}

.badge-GiftCard {
    color: #b45309;
    border-color: #fde68a;
    background: #fffbeb;
}

.td-sku {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    color: var(--text-lo);
    letter-spacing: 1px;
}

.td-price {
    font-family: 'Roboto Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--accent);
}

.stock-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border: 1px solid;
    display: inline-block;
}

.stock-ok {
    color: #16a34a;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.stock-low {
    color: #dc2626;
    border-color: #fecaca;
    background: #fef2f2;
}

.stock-zero {
    color: var(--text-lo);
    border-color: var(--border);
    background: var(--surface-2);
}

.btn-icon {
    width: 30px;
    height: 30px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.12s;
}

.btn-icon svg {
    width: 13px;
    height: 13px;
}

.btn-icon.edit:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(37,99,235,.08);
}

.btn-icon.add-stock:hover {
    border-color: var(--ok);
    color: var(--ok);
    background: #f0fdf4;
}

.btn-icon.del:hover {
    border-color: var(--heat);
    color: var(--heat);
    background: #fef2f2;
}

.empty-row td {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-lo);
    background: var(--surface);
}

.empty-row .e-label {
    font-size: 12px;
    color: var(--text-lo);
}

/* Modal */
.modal-bg {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.45);
    backdrop-filter: blur(3px);
    z-index: 500;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-bg.open {
    display: flex;
}

.modal-box {
    background: var(--surface);
    border: 1px solid var(--border);
    width: 100%;
    max-width: 480px;
    box-shadow: 0 10px 15px -3px rgba(15,23,42,.08), 0 4px 6px -4px rgba(15,23,42,.04);
}

.modal-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-hi);
    padding: 16px 22px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-title::before {
    content: '';
    width: 4px;
    height: 18px;
    background: var(--accent);
    flex-shrink: 0;
}

.modal-body-pad {
    padding: 20px 22px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 20px 22px;
}

.form-full {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-lo);
}

.form-inp, .form-sel {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-hi);
    padding: 8px 10px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    transition: all 0.15s;
    width: 100%;
    box-shadow: 0 1px 2px rgba(15,23,42,.05);
}

.form-inp:focus, .form-sel:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(37,99,235,.18);
}

.form-inp::placeholder {
    color: var(--text-lo);
}

.form-sel {
    appearance: none;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px 12px;
    padding-right: 32px !important;
}

.form-sel option {
    background: var(--surface);
}

.form-inp.mono-inp {
    font-family: 'Roboto Mono', monospace;
}

.form-err {
    font-size: 11px;
    color: var(--heat);
    font-family: 'Roboto Mono', monospace;
    min-height: 16px;
    padding: 0 22px 4px;
}

.modal-footer {
    display: flex;
    gap: 10px;
    padding: 14px 22px;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
}

.btn-cancel {
    flex: 1;
    padding: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text-lo);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.12s;
    font-family: 'Inter', sans-serif;
}

.btn-cancel:hover {
    background: var(--surface-2);
    border-color: #cbd5e1;
    color: var(--text);
}

.btn-save {
    flex: 2;
    padding: 10px;
    background: var(--accent);
    border: none;
    color: white;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-save:hover:not(:disabled) {
    background: #1d4ed8;
}

.btn-save:disabled {
    background: var(--surface-3);
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: not-allowed;
    box-shadow: none;
}

/* Delete confirm modal */
.modal-box.danger {
    border-color: #fecaca;
}

.modal-title.danger::before {
    background: var(--heat);
}

.modal-title.danger {
    color: var(--heat);
}

.btn-delete {
    flex: 2;
    padding: 10px;
    background: var(--heat);
    border: none;
    color: white;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
}

.btn-delete:hover {
    background: #b91c1c;
}

/* Toast */
#toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 11px 16px;
    background: var(--text-hi);
    color: white;
    font-size: 13px;
    z-index: 9999;
    max-width: 320px;
    transform: translateY(60px);
    opacity: 0;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    pointer-events: none;
    box-shadow: 0 10px 15px -3px rgba(15,23,42,.08);
    display: flex;
    align-items: center;
    gap: 10px;
}

#toast::before {
    content: '';
    width: 6px;
    height: 6px;
    flex-shrink: 0;
    background: white;
    opacity: 0.7;
}

#toast.show {
    transform: translateY(0);
    opacity: 1;
}

#toast.toast-ok {
    background: var(--ok);
}

#toast.toast-err {
    background: var(--heat);
}

/* Modal animations */
@keyframes modal-backdrop-in {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes modal-box-in {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-bg.open {
    animation: modal-backdrop-in 0.15s ease-out;
}

.modal-bg.open .modal-box {
    animation: modal-box-in 0.2s cubic-bezier(0.4,0,0.2,1);
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

    <a href="dashboard.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="index.php" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Point of Sale
    </a>

    <div class="nav-item active expanded" id="nav-products-toggle" onclick="toggleProductsNav()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Products
        <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
    <div class="nav-sub open" id="nav-products-sub">
        <a class="nav-sub-item" data-platform="all" onclick="setPlatformFilter('all');return false;" href="#">All</a>
        <a class="nav-sub-item" data-platform="PC" onclick="setPlatformFilter('PC');return false;" href="#">PC</a>
        <a class="nav-sub-item" data-platform="PS5" onclick="setPlatformFilter('PS5');return false;" href="#">PS5</a>
        <a class="nav-sub-item" data-platform="Switch" onclick="setPlatformFilter('Switch');return false;" href="#">Switch</a>
        <a class="nav-sub-item" data-platform="Xbox" onclick="setPlatformFilter('Xbox');return false;" href="#">Xbox</a>
        <a class="nav-sub-item" data-platform="GiftCard" onclick="setPlatformFilter('GiftCard');return false;" href="#">Gift Cards</a>
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
           onmouseover="this.style.background='#fef2f2'"
           onmouseout="this.style.background='transparent'">Logout</a>
    </div>
</nav>

<!-- Main content -->
<div id="main-panel">

    <div class="page-toolbar" style="flex-direction:column;align-items:stretch;gap:10px">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px">
            <span class="page-title">Product Catalog</span>
            <div class="toolbar-right">
                <div class="search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="search-products" placeholder="Search title, SKU, platform...">
                </div>
                <button class="btn-add" onclick="openAddModal()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Product
                </button>
            </div>
        </div>
        <div class="filter-tabs">
            <button class="ftab active" data-platform="all"      onclick="setPlatformFilter('all')">All</button>
            <button class="ftab"        data-platform="PC"       onclick="setPlatformFilter('PC')">PC</button>
            <button class="ftab"        data-platform="PS5"      onclick="setPlatformFilter('PS5')">PS5</button>
            <button class="ftab"        data-platform="Switch"   onclick="setPlatformFilter('Switch')">Switch</button>
            <button class="ftab"        data-platform="Xbox"     onclick="setPlatformFilter('Xbox')">Xbox</button>
            <button class="ftab"        data-platform="GiftCard" onclick="setPlatformFilter('GiftCard')">Gift Cards</button>
        </div>
    </div>

    <div class="table-wrap">
        <table id="products-table">
            <thead>
                <tr>
                    <th style="width:90px">Platform</th>
                    <th>Title</th>
                    <th>SKU</th>
                    <th style="width:80px">Format</th>
                    <th style="width:80px;text-align:right">Stock</th>
                    <th style="width:110px;text-align:right">Price</th>
                    <th style="width:110px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody id="products-tbody">
                <!-- JS rendered -->
            </tbody>
        </table>
    </div>
</div>

</div><!-- #app -->

<div id="toast"></div>

<!-- Add / edit modal -->
<div class="modal-bg" id="modal-form">
    <div class="modal-box">
        <div class="modal-title" id="form-modal-title">Add Product</div>
        <input type="hidden" id="form-id">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Title</label>
                <input class="form-inp" id="form-title" placeholder="e.g. God of War: Ragnarök" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Platform</label>
                <select class="form-sel" id="form-platform">
                    <option value="">— Select —</option>
                    <option value="PS5">PS5</option>
                    <option value="Switch">Switch</option>
                    <option value="Xbox">Xbox</option>
                    <option value="PC">PC</option>
                    <option value="GiftCard">Gift Card</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Format</label>
                <select class="form-sel" id="form-format">
                    <option value="Disk">Disk</option>
                    <option value="Card">Card</option>
                    <option value="Digital">Digital</option>
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">SKU</label>
                <input class="form-inp mono-inp" id="form-sku" placeholder="e.g. PS5-GWR-001" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Stock (qty)</label>
                <input class="form-inp mono-inp" type="number" id="form-stock" min="0" placeholder="0">
            </div>
            <div class="form-group">
                <label class="form-label">Price (₱)</label>
                <input class="form-inp mono-inp" type="number" id="form-price" min="0.01" step="0.01" placeholder="0.00">
            </div>
        </div>
        <div class="form-err" id="form-err"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-form')">Cancel</button>
            <button class="btn-save" id="btn-save-product" onclick="saveProduct()">Save Product</button>
        </div>
    </div>
</div>

<!-- Delete confirm modal -->
<div class="modal-bg" id="modal-delete">
    <div class="modal-box danger">
        <div class="modal-title danger">Confirm Delete</div>
        <div style="padding:20px 22px">
            <p style="color:var(--text);line-height:1.6;margin-bottom:8px">
                Are you sure you want to delete <strong id="del-product-name" style="color:var(--text-hi)"></strong>?
            </p>
            <p style="color:var(--text-lo);font-size:11px;font-family:'Roboto Mono',monospace">
                Products with sales history cannot be deleted.
            </p>
        </div>
        <input type="hidden" id="del-product-id">
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancel</button>
            <button class="btn-delete" id="btn-confirm-delete" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Add stock modal -->
<div class="modal-bg" id="modal-add-stock">
    <div class="modal-box">
        <div class="modal-title">Add Stock</div>
        <div class="modal-body-pad">
            <div id="add-stock-name" style="font-size:13px;font-weight:600;color:var(--text-hi);margin-bottom:10px"></div>
            <div style="font-size:11px;color:var(--text-lo);margin-bottom:14px">
                Current stock: <span id="add-stock-current" style="font-family:'Roboto Mono',monospace;font-weight:700;color:var(--text-hi)"></span> units
            </div>
            <div class="form-group">
                <label class="form-label">Quantity to Add</label>
                <input class="form-inp mono-inp" type="number" id="add-stock-qty" min="1" step="1" placeholder="e.g. 10">
            </div>
        </div>
        <input type="hidden" id="add-stock-id">
        <div class="form-err" id="add-stock-err"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-add-stock')">Cancel</button>
            <button class="btn-save" id="btn-confirm-add-stock" onclick="confirmAddStock()" style="background:var(--ok)">Add Stock</button>
        </div>
    </div>
</div>

<script>
let products = <?= json_encode(array_values($products), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
let platformFilter = <?= json_encode(
    is_string($_GET['platform'] ?? null) ? $_GET['platform'] : 'all',
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

// Render table
function renderTable() {
    const q     = document.getElementById('search-products').value.toLowerCase();
    const tbody = document.getElementById('products-tbody');
    const filtered = products.filter(p => {
        const matchesSearch = !q
            || p.title.toLowerCase().includes(q)
            || p.sku.toLowerCase().includes(q)
            || p.platform.toLowerCase().includes(q);
        const matchesPlatform = platformFilter === 'all' || p.platform === platformFilter;
        return matchesSearch && matchesPlatform;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7">
            <div class="e-label">${q ? 'No products match your search' : 'No products yet — click Add Product'}</div>
        </td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const stockClass = p.stock === 0 ? 'stock-zero' : p.stock < 5 ? 'stock-low' : 'stock-ok';
        const badgeClass = `badge-${esc(p.platform)}`;
        return `<tr data-id="${p.id}">
            <td><span class="badge ${badgeClass}">${esc(p.platform)}</span></td>
            <td style="font-weight:600;color:var(--text-hi)">${esc(p.title)}</td>
            <td><span class="td-sku">${esc(p.sku)}</span></td>
            <td style="font-size:11px;color:var(--text-lo);text-transform:uppercase;letter-spacing:.5px">${esc(p.format)}</td>
            <td style="text-align:right"><span class="stock-val ${stockClass}">${p.stock}</span></td>
            <td style="text-align:right"><span class="td-price">₱${parseFloat(p.price).toLocaleString('en-PH',{minimumFractionDigits:2})}</span></td>
            <td style="text-align:center">
                <div style="display:inline-flex;gap:5px">
                    <button class="btn-icon edit" title="Edit" onclick="openEditModal(${p.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon add-stock" title="Add Stock" onclick="openAddStockModal(${p.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <button class="btn-icon del" title="Delete" onclick="openDeleteModal(${p.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// Platform filter
function setPlatformFilter(platform) {
    platformFilter = platform;
    // Highlight the active tab and sidebar link
    document.querySelectorAll('.ftab')
        .forEach(t => t.classList.toggle('active', t.dataset.platform === platform));
    document.querySelectorAll('.nav-sub-item')
        .forEach(t => t.classList.toggle('active', t.dataset.platform === platform));
    renderTable();
}

function toggleProductsNav() {
    const toggle = document.getElementById('nav-products-toggle');
    const sub    = document.getElementById('nav-products-sub');
    const open   = toggle.classList.toggle('expanded');
    sub.classList.toggle('open', open);
    sub.style.maxHeight = open ? sub.scrollHeight + 'px' : '0';
}

// Keep the Products sub-menu open on this page
document.addEventListener('DOMContentLoaded', () => {
    const sub = document.getElementById('nav-products-sub');
    if (sub && sub.classList.contains('open')) sub.style.maxHeight = sub.scrollHeight + 'px';
});

// Modal helpers
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => {
    if (e.target === m) closeModal(m.id);
}));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach(m => closeModal(m.id));
});

// Add / edit modal
function resetForm() {
    document.getElementById('form-id').value       = '';
    document.getElementById('form-title').value    = '';
    document.getElementById('form-platform').value = '';
    document.getElementById('form-format').value   = 'Disk';
    document.getElementById('form-sku').value      = '';
    document.getElementById('form-stock').value    = '';
    document.getElementById('form-price').value    = '';
    document.getElementById('form-err').textContent = '';
    document.getElementById('btn-save-product').disabled = false;
    document.getElementById('btn-save-product').textContent = 'Save Product';
}

function openAddModal() {
    resetForm();
    document.getElementById('form-modal-title').textContent = '// Add Product';
    openModal('modal-form');
    setTimeout(() => document.getElementById('form-title').focus(), 80);
}

function openEditModal(id) {
    const p = products.find(x => x.id == id);
    if (!p) return;
    resetForm();
    document.getElementById('form-modal-title').textContent = '// Edit Product';
    document.getElementById('form-id').value       = p.id;
    document.getElementById('form-title').value    = p.title;
    document.getElementById('form-platform').value = p.platform;
    document.getElementById('form-format').value   = p.format;
    document.getElementById('form-sku').value      = p.sku;
    document.getElementById('form-stock').value    = p.stock;
    document.getElementById('form-price').value    = parseFloat(p.price).toFixed(2);
    openModal('modal-form');
    setTimeout(() => document.getElementById('form-title').focus(), 80);
}

async function saveProduct() {
    const btn = document.getElementById('btn-save-product');
    const err = document.getElementById('form-err');

    const payload = {
        id:       document.getElementById('form-id').value ? parseInt(document.getElementById('form-id').value) : null,
        title:    document.getElementById('form-title').value.trim(),
        platform: document.getElementById('form-platform').value,
        format:   document.getElementById('form-format').value,
        sku:      document.getElementById('form-sku').value.trim().toUpperCase(),
        stock:    parseInt(document.getElementById('form-stock').value) || 0,
        price:    parseFloat(document.getElementById('form-price').value) || 0,
    };

    // Quick check (the API checks again)
    if (!payload.title) {
        err.textContent = 'Title is required';
        return;
    }
    if (!payload.platform) {
        err.textContent = 'Platform is required';
        return;
    }
    if (!payload.sku) {
        err.textContent = 'SKU is required';
        return;
    }
    if (payload.price <= 0) {
        err.textContent = 'Price must be greater than 0';
        return;
    }

    err.textContent  = '';
    btn.disabled     = true;
    btn.textContent  = 'Saving...';

    try {
        const res  = await fetch('api/product_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            closeModal('modal-form');
            showToast(payload.id ? 'Product updated' : 'Product added', 'ok');
            await refreshProducts();
        } else {
            err.textContent = data.error || 'Save failed';
        }
    } catch (e) {
        err.textContent = 'Network error: ' + e.message;
    }

    btn.disabled    = false;
    btn.textContent = 'Save Product';
}

// Delete modal
function openDeleteModal(id) {
    const p = products.find(x => x.id == id);
    if (!p) return;
    document.getElementById('del-product-id').value     = id;
    document.getElementById('del-product-name').textContent = p.title;
    document.getElementById('btn-confirm-delete').disabled = false;
    document.getElementById('btn-confirm-delete').textContent = 'Delete';
    openModal('modal-delete');
}

async function confirmDelete() {
    const btn = document.getElementById('btn-confirm-delete');
    const id  = parseInt(document.getElementById('del-product-id').value);

    btn.disabled    = true;
    btn.textContent = 'Deleting...';

    try {
        const res  = await fetch('api/product_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.success) {
            closeModal('modal-delete');
            showToast('Product deleted', 'ok');
            await refreshProducts();
        } else {
            showToast(data.error || 'Delete failed', 'err');
            closeModal('modal-delete');
        }
    } catch (e) {
        showToast('Network error: ' + e.message, 'err');
    }

    btn.disabled    = false;
    btn.textContent = 'Delete';
}

// Add stock modal
function openAddStockModal(id) {
    const p = products.find(x => x.id == id);
    if (!p) return;
    document.getElementById('add-stock-id').value       = id;
    document.getElementById('add-stock-name').textContent = p.title;
    document.getElementById('add-stock-current').textContent = p.stock;
    document.getElementById('add-stock-qty').value      = '';
    document.getElementById('add-stock-err').textContent = '';
    document.getElementById('btn-confirm-add-stock').disabled   = false;
    document.getElementById('btn-confirm-add-stock').textContent = 'Add Stock';
    openModal('modal-add-stock');
    setTimeout(() => document.getElementById('add-stock-qty').focus(), 80);
}

async function confirmAddStock() {
    const btn = document.getElementById('btn-confirm-add-stock');
    const err = document.getElementById('add-stock-err');
    const id  = parseInt(document.getElementById('add-stock-id').value);
    const qty = parseInt(document.getElementById('add-stock-qty').value);

    if (!qty || qty <= 0) {
        err.textContent = 'Enter a valid quantity greater than 0';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Adding...';
    err.textContent = '';

    try {
        const res  = await fetch('api/product_stock_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, qty })
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-add-stock');
            showToast(`Added ${qty} units — new stock: ${data.new_stock}`, 'ok');
            await refreshProducts();
        } else {
            err.textContent = data.error || 'Failed to add stock';
        }
    } catch (e) {
        err.textContent = 'Network error: ' + e.message;
    }
    btn.disabled = false;
    btn.textContent = 'Add Stock';
}

// Data refresh
async function refreshProducts() {
    try {
        const res = await fetch('api/products.php');
        products  = await res.json();
        renderTable();
    } catch (e) {
        showToast('Refresh failed: ' + e.message, 'err');
    }
}

// Search
let searchDeb;
document.getElementById('search-products').addEventListener('input', () => {
    clearTimeout(searchDeb);
    searchDeb = setTimeout(renderTable, 140);
});

// Toast
let toastTimer;
function showToast(msg, type = 'info') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = `show toast-${type}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.className = '', 3000);
}

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    // Apply ?platform= from the URL
    if (platformFilter && platformFilter !== 'all') {
        document.querySelectorAll('.ftab')
            .forEach(t => t.classList.toggle('active', t.dataset.platform === platformFilter));
        document.querySelectorAll('.nav-sub-item')
            .forEach(t => t.classList.toggle('active', t.dataset.platform === platformFilter));
    } else {
        document.querySelectorAll('.ftab[data-platform="all"]').forEach(t => t.classList.add('active'));
        document.querySelectorAll('.nav-sub-item[data-platform="all"]').forEach(t => t.classList.add('active'));
    }
    renderTable();
});
</script>
</body>
</html>
