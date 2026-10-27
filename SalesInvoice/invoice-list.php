<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$customers = $pdo->query("
    SELECT id, name, billing_address, shipping_address
    FROM customers
    ORDER BY name
")->fetchAll();
// Only in-stock products can be added as line items
$products = $pdo->query("
    SELECT id, title, platform, price
    FROM products
    WHERE stock > 0
    ORDER BY FIELD(platform, 'PC', 'PS5', 'Switch', 'Xbox', 'GiftCard', 'Other'), title
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice Manager - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #eef2f7;
    --surface: #fff;
    --surface-2: #f8fafc;
    --surface-3: #f1f5f9;
    --accent: #2563eb;
    --accent-light: #eff6ff;
    --accent-dim: rgba(37,99,235,.08);
    --accent-glow: rgba(37,99,235,.18);
    --border: #e2e8f0;
    --border-med: #cbd5e1;
    --text: #0f172a;
    --text-2: #334155;
    --text-3: #64748b;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --heat-bg: #fef2f2;
    --ok: #16a34a;
    --ok-bg: #f0fdf4;
    --warn: #d97706;
    --sh-xs: 0 1px 2px rgba(15,23,42,.05);
    --sh-sm: 0 1px 3px rgba(15,23,42,.08);
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
    transition: background .18s ease, color .18s ease, border-left-color .18s ease;
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
    transition: max-height .32s cubic-bezier(.4,0,.2,1);
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
    transition: background .18s ease, color .18s ease, border-left-color .18s ease;
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
    transition: background .18s ease;
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
    transition: transform .30s cubic-bezier(.4,0,.2,1);
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
    background: var(--bg);
}

.page-toolbar {
    flex-shrink: 0;
    padding: 14px 20px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--sh-sm);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.toolbar-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.page-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
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

#search-inv {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 10px 8px 32px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    width: 240px;
    transition: all .15s;
    box-shadow: var(--sh-xs);
}

#search-inv:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

#search-inv::placeholder {
    color: var(--text-lo);
}

.btn-primary {
    padding: 8px 18px;
    background: var(--accent);
    border: none;
    color: white;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all .15s;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-primary:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 10px rgba(37,99,235,.35);
}

/* Filter chips */
.filter-chips {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.fchip {
    padding: 4px 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    cursor: pointer;
    transition: background .18s ease, border-color .18s ease, color .18s ease;
    color: var(--text-3);
}

.fchip:hover {
    background: var(--surface-2);
    border-color: var(--border-med);
    color: var(--text);
}

.fchip.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

.fchip.fchip-open {
}

.fchip.fchip-open.active {
    background: #d97706;
    border-color: #d97706;
}

.fchip.fchip-paid.active {
    background: var(--ok);
    border-color: var(--ok);
}

/* Table */
.table-wrap {
    flex: 1;
    overflow-y: auto;
}

.table-wrap::-webkit-scrollbar {
    width: 6px;
}

.table-wrap::-webkit-scrollbar-thumb {
    background: var(--border-med);
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
    transition: background .1s;
    cursor: pointer;
}

tbody tr:hover td {
    background: var(--accent-light);
}

.inv-no {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
}

.td-total {
    font-family: 'Roboto Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
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

.badge-invoice {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
}

.badge-quote {
    color: #7c3aed;
    border-color: #ddd6fe;
    background: #f5f3ff;
}

.badge-receipt {
    color: #0f766e;
    border-color: #99f6e4;
    background: #f0fdfa;
}

.badge-open {
    color: #d97706;
    border-color: #fde68a;
    background: #fffbeb;
}

.badge-paid {
    color: #16a34a;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.btn-icon {
    width: 28px;
    height: 28px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all .15s;
}

.btn-icon svg {
    width: 12px;
    height: 12px;
}

.btn-icon.edit:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-dim);
}

.btn-icon.del:hover {
    border-color: var(--heat);
    color: var(--heat);
    background: var(--heat-bg);
}

.btn-icon.status-toggle:hover {
    border-color: var(--ok);
    color: var(--ok);
    background: var(--ok-bg);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    text-align: center;
}

.empty-state svg {
    margin-bottom: 16px;
    opacity: .2;
}

.empty-state .e-t {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-3);
}

.empty-state .e-s {
    font-size: 11px;
    color: var(--text-lo);
    margin-top: 4px;
}

/* Wide create/edit modal */
.modal-bg {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.45);
    backdrop-filter: blur(3px);
    z-index: 500;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

.modal-bg.open {
    display: flex;
}

.modal-box {
    background: var(--surface);
    border: 1px solid var(--border);
    width: 100%;
    max-width: 800px;
    box-shadow: var(--sh-lg);
    margin: auto;
}

.modal-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    padding: 16px 22px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
    display: flex;
    align-items: center;
    gap: 8px;
    position: sticky;
    top: 0;
    z-index: 2;
}

.modal-title::before {
    content: '';
    width: 4px;
    height: 18px;
    background: var(--accent);
    flex-shrink: 0;
}

.modal-title.danger::before {
    background: var(--heat);
}

.form-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 18px 22px 0;
}

.form-section {
    padding: 0 22px;
    margin-top: 18px;
}

.form-section-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-3);
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--border);
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
    color: var(--text-3);
}

.form-inp, .form-sel, .form-textarea {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 10px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    transition: all .15s;
    width: 100%;
    box-shadow: var(--sh-xs);
}

.form-inp:focus, .form-sel:focus, .form-textarea:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

.form-inp::placeholder, .form-textarea::placeholder {
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

select.li-inp {
    appearance: none;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 10px 10px;
    padding-right: 24px !important;
}

.form-textarea {
    resize: vertical;
    min-height: 56px;
}

.form-err {
    font-size: 11px;
    color: var(--heat);
    font-family: 'Roboto Mono', monospace;
    min-height: 16px;
    padding: 4px 22px;
}

/* Line items builder */
.items-section {
    padding: 0 22px;
    margin-top: 18px;
}

.items-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 32px;
    gap: 6px;
    padding: 6px 0;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-lo);
    border-bottom: 1px solid var(--border);
}

.line-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 32px;
    gap: 6px;
    padding: 6px 0;
    border-bottom: 1px solid var(--border);
    align-items: center;
}

.li-inp {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 6px 8px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    outline: none;
    width: 100%;
    transition: all .15s;
}

.li-inp:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 2px var(--accent-glow);
}

.li-inp.mono {
    font-family: 'Roboto Mono', monospace;
}

.li-sub {
    font-family: 'Roboto Mono', monospace;
    font-size: 12px;
    font-weight: 700;
    color: var(--accent);
    text-align: right;
    padding-right: 4px;
}

.li-del-btn {
    width: 28px;
    height: 28px;
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .12s;
    font-size: 14px;
}

.li-del-btn:hover {
    border-color: var(--heat);
    color: var(--heat);
    background: var(--heat-bg);
}

.btn-add-line {
    margin-top: 10px;
    padding: 6px 14px;
    background: transparent;
    border: 1px dashed var(--border-med);
    color: var(--text-3);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-add-line:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-light);
}

.totals-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 14px 22px 0;
    border-top: 1px solid var(--border);
    margin-top: 14px;
}

.totals-col {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}

.total-lbl {
    color: var(--text-3);
}

.total-val {
    font-family: 'Roboto Mono', monospace;
    font-weight: 600;
    color: var(--text);
}

.grand-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-top: 2px solid var(--accent);
    margin-top: 6px;
}

.grand-lbl {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
}

.grand-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 18px;
    font-weight: 700;
    color: var(--accent);
}

.modal-footer {
    display: flex;
    gap: 10px;
    padding: 14px 22px;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
    position: sticky;
    bottom: 0;
    z-index: 2;
}

.btn-cancel {
    flex: 1;
    padding: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text-3);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all .12s;
    font-family: 'Inter', sans-serif;
}

.btn-cancel:hover {
    background: var(--surface-2);
    border-color: var(--border-med);
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
    transition: all .15s;
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

.btn-delete-modal {
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
    transition: all .15s;
    font-family: 'Inter', sans-serif;
}

.btn-delete-modal:hover {
    background: #b91c1c;
}

#toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 11px 16px;
    background: var(--text);
    color: white;
    font-size: 13px;
    z-index: 9999;
    max-width: 320px;
    transform: translateY(60px);
    opacity: 0;
    transition: all .25s cubic-bezier(.4,0,.2,1);
    pointer-events: none;
    box-shadow: var(--sh-lg);
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
    opacity: .7;
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

#toast.toast-warn {
    background: var(--warn);
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

<nav id="nav-panel">
    <div class="brand">
        <div class="brand-gg">SI</div>
        <div class="brand-name">Sales &amp; Invoicing System</div>
    </div>
    <div class="nav-section-label">Main Menu</div>
    <a href="dashboard.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard
    </a>
    <a href="index.php" class="nav-item" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>Point of Sale
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
    <a href="invoice-list.php" class="nav-item active" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
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
           style="display:block;padding:6px 10px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-align:center;text-decoration:none;color:var(--heat);border:1px solid #fecaca;transition:all .12s"
           onmouseover="this.style.background='var(--heat-bg)'"
           onmouseout="this.style.background='transparent'">Logout</a>
    </div>
</nav>

<div id="main-panel">
    <div class="page-toolbar">
        <div class="toolbar-top">
            <span class="page-title">Invoice Manager</span>
            <div style="display:flex;align-items:center;gap:10px">
                <div class="search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="search-inv" placeholder="Search invoice no, customer...">
                </div>
                <button class="btn-primary" onclick="openCreateModal()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Invoice
                </button>
            </div>
        </div>
        <div class="filter-chips">
            <button class="fchip active" data-filter="all" onclick="setFilter('all')">All</button>
            <button class="fchip fchip-open" data-filter="open" onclick="setFilter('open')">Open</button>
            <button class="fchip fchip-paid" data-filter="paid" onclick="setFilter('paid')">Paid</button>
            <button class="fchip" data-filter="invoice" onclick="setFilter('invoice')">Invoice</button>
            <button class="fchip" data-filter="quote" onclick="setFilter('quote')">Quote</button>
            <button class="fchip" data-filter="receipt" onclick="setFilter('receipt')">Receipt</button>
        </div>
    </div>

    <div class="table-wrap" id="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Customer</th>
                    <th style="width:60px;text-align:center">Items</th>
                    <th style="text-align:right">Total</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th style="width:110px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody id="inv-tbody">
                <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-lo)">Loading...</td></tr>
            </tbody>
        </table>
        <div id="empty-state" class="empty-state" style="display:none">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div class="e-t">No invoices yet</div>
            <div class="e-s">Click "New Invoice" to create your first one</div>
        </div>
    </div>
</div>
</div>

<div id="toast"></div>

<!-- Create / edit modal -->
<div class="modal-bg" id="modal-form">
    <div class="modal-box">
        <div class="modal-title" id="modal-form-title">New Invoice</div>
        <input type="hidden" id="f-id">

        <div class="form-2col">
            <div class="form-group">
                <label class="form-label">Invoice Type</label>
                <select class="form-sel" id="f-type">
                    <option value="invoice">Invoice</option>
                    <option value="quote">Quote</option>
                    <option value="receipt">Receipt</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select class="form-sel" id="f-status">
                    <option value="open">Open</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Issue Date *</label>
                <input class="form-inp" type="date" id="f-date">
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input class="form-inp" type="date" id="f-due">
            </div>
        </div>

        <div class="form-section" style="margin-top:14px">
            <div class="form-section-title">Customer / Billed To</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group">
                    <label class="form-label">Select Existing Customer</label>
                    <select class="form-sel" id="f-customer-sel" onchange="applyCustomer()">
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"
                                data-name="<?= htmlspecialchars($c['name']) ?>"
                                data-billing="<?= htmlspecialchars($c['billing_address']??'') ?>"
                                data-shipping="<?= htmlspecialchars($c['shipping_address']??'') ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Customer Name *</label>
                    <input class="form-inp" id="f-cname" placeholder="Full name or company">
                </div>
                <div class="form-group">
                    <label class="form-label">Billing Address</label>
                    <textarea class="form-textarea" id="f-billing" placeholder="Street, City, Province"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Shipping Address</label>
                    <textarea class="form-textarea" id="f-shipping" placeholder="Leave blank if same as billing"></textarea>
                </div>
            </div>
        </div>

        <!-- Line items -->
        <div class="items-section">
            <div class="form-section-title">Line Items</div>
            <div class="items-header">
                <span>Description</span><span>Qty</span><span>Unit Price</span><span style="text-align:right">Subtotal</span><span></span>
            </div>
            <div id="line-items"></div>
            <button class="btn-add-line" onclick="addLine()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Line Item
            </button>
        </div>

        <!-- Totals + extra fields -->
        <div class="totals-preview">
            <div class="totals-col">
                <div class="form-group">
                    <label class="form-label">Global Discount (₱)</label>
                    <input class="form-inp" type="number" id="f-discount" min="0" step="0.01" placeholder="0.00" value="0" oninput="recalcTotals()">
                </div>
                <div class="form-group">
                    <label class="form-label">Shipping (₱)</label>
                    <input class="form-inp" type="number" id="f-shipping-cost" min="0" step="0.01" placeholder="0.00" value="0" oninput="recalcTotals()">
                </div>
            </div>
            <div class="totals-col">
                <div class="total-row"><span class="total-lbl">Subtotal</span><span class="total-val" id="t-sub">₱0.00</span></div>
                <div class="total-row"><span class="total-lbl">Discount</span><span class="total-val" id="t-disc" style="color:var(--heat)">—</span></div>
                <div class="total-row"><span class="total-lbl">Shipping</span><span class="total-val" id="t-ship">—</span></div>
                <div class="total-row"><span class="total-lbl">VAT (12%)</span><span class="total-val" id="t-vat">₱0.00</span></div>
                <div class="grand-row"><span class="grand-lbl">Total Due</span><span class="grand-val" id="t-grand">₱0.00</span></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px 22px">
            <div class="form-group">
                <label class="form-label">Notes (Internal)</label>
                <textarea class="form-textarea" id="f-notes" placeholder="Internal notes..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Custom Message (Printed)</label>
                <textarea class="form-textarea" id="f-message" placeholder="Thank you for your business!"></textarea>
            </div>
        </div>

        <div class="form-err" id="form-err"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-form')">Cancel</button>
            <button class="btn-save" id="btn-save" onclick="saveInvoice()">Save Invoice</button>
        </div>
    </div>
</div>

<!-- Delete confirm -->
<div class="modal-bg" id="modal-delete">
    <div class="modal-box" style="max-width:400px;margin:auto">
        <div class="modal-title danger">Confirm Delete</div>
        <div style="padding:20px 22px">
            <p style="color:var(--text-2);line-height:1.6">
                Delete invoice <strong id="del-inv-no" style="color:var(--accent)"></strong>? This cannot be undone.
            </p>
        </div>
        <input type="hidden" id="del-id">
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancel</button>
            <button class="btn-delete-modal" id="btn-del" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
const customers  = <?= json_encode(array_values($customers), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const products   = <?= json_encode(array_values($products),  JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const fmtPHP = n => '₱' + parseFloat(n || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

let allInvoices = [];
let filterMode  = 'all';
let searchQ     = '';

// Render table
function renderTable() {
    const q     = searchQ.toLowerCase();
    const tbody = document.getElementById('inv-tbody');
    const empty = document.getElementById('empty-state');

    let filtered = allInvoices.filter(i => {
        const matchesSearch = !q
            || i.invoice_no.toLowerCase().includes(q)
            || i.customer_name.toLowerCase().includes(q);
        if (!matchesSearch) return false;
        if (filterMode === 'all')    return true;
        if (filterMode === 'open')   return i.status === 'open';
        if (filterMode === 'paid')   return i.status === 'paid';
        return i.invoice_type === filterMode;
    });

    if (allInvoices.length === 0) {
        tbody.innerHTML = '';
        empty.style.display = 'flex';
        return;
    }
    empty.style.display = 'none';

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-lo)">No invoices match your filter</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(i => {
        const issueDate = i.invoice_date
            ? new Date(i.invoice_date + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
            : '—';
        const dueDate = i.due_date
            ? new Date(i.due_date + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
            : '—';
        const isPastDue = i.due_date && i.status === 'open' && new Date(i.due_date) < new Date();
        return `<tr onclick="openEditModal(${i.id})" title="Click to edit invoice">
            <td><span class="inv-no">${esc(i.invoice_no)}</span></td>
            <td><span class="badge badge-${i.invoice_type}">${i.invoice_type}</span></td>
            <td><span class="badge badge-${i.status}">${i.status}</span></td>
            <td style="font-weight:600;color:var(--text);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(i.customer_name)}</td>
            <td style="text-align:center;font-family:'Roboto Mono',monospace;font-weight:600">${i.item_count}</td>
            <td style="text-align:right"><span class="td-total">${fmtPHP(i.total_amount)}</span></td>
            <td style="font-size:11px;color:var(--text-3)">${issueDate}</td>
            <td style="font-size:11px;color:${isPastDue?'var(--heat)':'var(--text-3)'}">${dueDate}</td>
            <td style="text-align:center">
                <div style="display:inline-flex;gap:4px">
                    <button class="btn-icon status-toggle" title="${i.status==='open'?'Mark Paid':'Mark Open'}" onclick="event.stopPropagation();toggleStatus(${i.id},'${i.status==='open'?'paid':'open'}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                    <button class="btn-icon edit" title="Edit" onclick="event.stopPropagation();openEditModal(${i.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon del" title="Delete" onclick="event.stopPropagation();openDeleteModal(${i.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function setFilter(f) {
    filterMode = f;
    document.querySelectorAll('.fchip').forEach(c => c.classList.toggle('active', c.dataset.filter === f));
    renderTable();
}

// Line items
let lineId = 0;

function addLine(name = '', qty = 1, price = 0, discount = 0, productId = null) {
    lineId++;
    const id = lineId;
    const container = document.getElementById('line-items');
    const row = document.createElement('div');
    row.className = 'line-row';
    row.id = 'lr-' + id;

    const productOptions = `<option value="">Custom</option>` + products.map(p =>
        `<option value="${p.id}" data-price="${p.price}" data-name="${esc(p.title)}" ${p.id == productId ? 'selected' : ''}>${esc(p.title)} (${esc(p.platform)})</option>`
    ).join('');

    row.innerHTML = `
        <div style="display:flex;flex-direction:column;gap:4px">
            <select class="li-inp" onchange="applyProductLine(${id},this)" style="font-size:11px">${productOptions}</select>
            <input class="li-inp" placeholder="Item description" value="${esc(name)}" oninput="recalcLine(${id})">
        </div>
        <input class="li-inp mono" type="number" min="0.01" step="0.01" value="${qty}" oninput="recalcLine(${id})">
        <input class="li-inp mono" type="number" min="0" step="0.01" value="${price}" oninput="recalcLine(${id})">
        <span class="li-sub" id="ls-${id}">${fmtPHP(qty*price-discount)}</span>
        <button class="li-del-btn" onclick="removeLine(${id})">✕</button>`;

    container.appendChild(row);
    recalcLine(id);
}

function applyProductLine(id, sel) {
    const opt = sel.selectedOptions[0];
    const row = document.getElementById('lr-' + id);
    if (!row) return;
    const inputs = row.querySelectorAll('input');
    if (opt.value) {
        inputs[0].value = opt.dataset.name || '';
        inputs[2].value = opt.dataset.price || '0';
    }
    recalcLine(id);
}

function recalcLine(id) {
    const row = document.getElementById('lr-' + id);
    if (!row) return;
    const inputs = row.querySelectorAll('input.li-inp.mono');
    const qty   = parseFloat(inputs[0]?.value) || 0;
    const price = parseFloat(inputs[1]?.value) || 0;
    const sub   = qty * price;
    const el    = document.getElementById('ls-' + id);
    if (el) el.textContent = fmtPHP(sub);
    recalcTotals();
}

function removeLine(id) {
    document.getElementById('lr-' + id)?.remove();
    recalcTotals();
}

function recalcTotals() {
    let sub = 0;
    document.querySelectorAll('.line-row').forEach(row => {
        const inputs = row.querySelectorAll('input.li-inp.mono');
        const qty    = parseFloat(inputs[0]?.value) || 0;
        const price  = parseFloat(inputs[1]?.value) || 0;
        sub += qty * price;
    });

    const disc  = Math.max(0, parseFloat(document.getElementById('f-discount')?.value) || 0);
    const ship  = Math.max(0, parseFloat(document.getElementById('f-shipping-cost')?.value) || 0);
    const netSub = Math.max(0, sub - disc);
    const vat   = netSub * 0.12;
    const total = netSub + vat + ship;

    document.getElementById('t-sub').textContent   = fmtPHP(sub);
    document.getElementById('t-disc').textContent  = disc > 0 ? `-${fmtPHP(disc)}` : '—';
    document.getElementById('t-ship').textContent  = ship > 0 ? fmtPHP(ship) : '—';
    document.getElementById('t-vat').textContent   = fmtPHP(vat);
    document.getElementById('t-grand').textContent = fmtPHP(total);
}

// Customer prefill
function applyCustomer() {
    const sel = document.getElementById('f-customer-sel');
    const opt = sel.selectedOptions[0];
    if (opt.value) {
        document.getElementById('f-cname').value    = opt.dataset.name    || '';
        document.getElementById('f-billing').value  = opt.dataset.billing  || '';
        document.getElementById('f-shipping').value = opt.dataset.shipping || '';
    }
}

// Modal helpers
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => {
    if (e.target === m) closeModal(m.id);
}));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach(m => closeModal(m.id));
});

function resetForm() {
    document.getElementById('f-id').value       = '';
    document.getElementById('f-type').value     = 'invoice';
    document.getElementById('f-status').value   = 'open';
    document.getElementById('f-date').value     = new Date().toISOString().slice(0, 10);
    document.getElementById('f-due').value      = '';
    document.getElementById('f-customer-sel').value = '';
    document.getElementById('f-cname').value    = '';
    document.getElementById('f-billing').value  = '';
    document.getElementById('f-shipping').value = '';
    document.getElementById('f-discount').value = '0';
    document.getElementById('f-shipping-cost').value = '0';
    document.getElementById('f-notes').value    = '';
    document.getElementById('f-message').value  = '';
    document.getElementById('form-err').textContent = '';
    document.getElementById('line-items').innerHTML  = '';
    lineId = 0;
    recalcTotals();
    const btn = document.getElementById('btn-save');
    btn.disabled = false;
    btn.textContent = 'Save Invoice';
}

function openCreateModal() {
    resetForm();
    document.getElementById('modal-form-title').textContent = '// New Invoice';
    addLine(); // start with one empty line
    openModal('modal-form');
}

async function openEditModal(id) {
    resetForm();
    document.getElementById('modal-form-title').textContent = '// Edit Invoice';
    try {
        const res  = await fetch(`api/invoices.php?id=${id}`);
        const data = await res.json();
        document.getElementById('f-id').value       = data.id;
        document.getElementById('f-type').value     = data.invoice_type;
        document.getElementById('f-status').value   = data.status;
        document.getElementById('f-date').value     = data.invoice_date;
        document.getElementById('f-due').value      = data.due_date || '';
        document.getElementById('f-customer-sel').value = data.customer_id || '';
        document.getElementById('f-cname').value    = data.customer_name;
        document.getElementById('f-billing').value  = data.billing_address  || '';
        document.getElementById('f-shipping').value = data.shipping_address || '';
        document.getElementById('f-discount').value = data.discount || '0';
        document.getElementById('f-shipping-cost').value = data.shipping_cost || '0';
        document.getElementById('f-notes').value    = data.notes          || '';
        document.getElementById('f-message').value  = data.custom_message || '';
        (data.items || []).forEach(it =>
            addLine(it.item_name, it.quantity, it.unit_price, it.discount, it.product_id)
        );
        recalcTotals();
    } catch (e) {
        showToast('Failed to load invoice', 'err');
        return;
    }
    openModal('modal-form');
}

// Save
async function saveInvoice() {
    const btn = document.getElementById('btn-save');
    const err = document.getElementById('form-err');

    const lineRows = [...document.querySelectorAll('.line-row')];
    const items = lineRows.map(row => {
        const sel    = row.querySelector('select');
        const inputs = row.querySelectorAll('input.li-inp');
        const monos  = row.querySelectorAll('input.li-inp.mono');
        return {
            product_id: sel?.value ? parseInt(sel.value) : null,
            item_name:  inputs[0]?.value?.trim() || 'Item',
            quantity:   parseFloat(monos[0]?.value) || 1,
            unit_price: parseFloat(monos[1]?.value) || 0,
            discount:   0,
        };
    }).filter(it => it.item_name && it.unit_price > 0);

    const payload = {
        action:           'save',
        id:               document.getElementById('f-id').value ? parseInt(document.getElementById('f-id').value) : 0,
        invoice_type:     document.getElementById('f-type').value,
        status:           document.getElementById('f-status').value,
        customer_id:      document.getElementById('f-customer-sel').value || null,
        customer_name:    document.getElementById('f-cname').value.trim(),
        billing_address:  document.getElementById('f-billing').value.trim(),
        shipping_address: document.getElementById('f-shipping').value.trim(),
        invoice_date:     document.getElementById('f-date').value,
        due_date:         document.getElementById('f-due').value || null,
        discount:         parseFloat(document.getElementById('f-discount').value) || 0,
        shipping_cost:    parseFloat(document.getElementById('f-shipping-cost').value) || 0,
        notes:            document.getElementById('f-notes').value.trim(),
        custom_message:   document.getElementById('f-message').value.trim(),
        items,
    };

    err.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const res  = await fetch('api/invoices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-form');
            showToast(payload.id ? 'Invoice updated' : `Invoice ${data.invoice_no} created`, 'ok');
            await load();
        } else {
            err.textContent = data.error || 'Save failed';
        }
    } catch (e) {
        err.textContent = 'Network error: ' + e.message;
    }

    btn.disabled = false;
    btn.textContent = 'Save Invoice';
}

// Status toggle
async function toggleStatus(id, newStatus) {
    try {
        const res  = await fetch('api/invoices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'status', id, status: newStatus }),
        });
        const data = await res.json();
        if (data.success) {
            showToast(`Marked ${newStatus}`, 'ok');
            await load();
        } else {
            showToast(data.error || 'Failed', 'err');
        }
    } catch (e) {
        showToast('Network error', 'err');
    }
}

// Delete
function openDeleteModal(id) {
    const inv = allInvoices.find(i => i.id == id);
    if (!inv) return;
    document.getElementById('del-id').value = id;
    document.getElementById('del-inv-no').textContent = inv.invoice_no;
    document.getElementById('btn-del').disabled = false;
    document.getElementById('btn-del').textContent = 'Delete';
    openModal('modal-delete');
}

async function confirmDelete() {
    const btn = document.getElementById('btn-del');
    const id  = parseInt(document.getElementById('del-id').value);
    btn.disabled = true;
    btn.textContent = 'Deleting...';
    try {
        const res  = await fetch('api/invoices.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-delete');
            showToast('Invoice deleted', 'ok');
            await load();
        } else {
            showToast(data.error || 'Delete failed', 'err');
            closeModal('modal-delete');
        }
    } catch (e) {
        showToast('Network error', 'err');
    }
    btn.disabled = false;
    btn.textContent = 'Delete';
}

// Load invoices
async function load() {
    try {
        const r = await fetch('api/invoices.php');
        allInvoices = await r.json();
        renderTable();
    } catch (e) {
        document.getElementById('inv-tbody').innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--heat)">Failed to load</td></tr>`;
    }
}

let searchDeb;
document.getElementById('search-inv').addEventListener('input', function() {
    clearTimeout(searchDeb);
    searchDeb = setTimeout(() => { searchQ = this.value; renderTable(); }, 140);
});

function toggleProductsNav() {
    const toggle = document.getElementById('nav-products-toggle');
    const sub    = document.getElementById('nav-products-sub');
    const open   = toggle.classList.toggle('expanded');
    sub.classList.toggle('open', open);
    sub.style.maxHeight = open ? sub.scrollHeight + 'px' : '0';
}

let toastTimer;
function showToast(msg, type = 'info') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `show toast-${type}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.className = '', 3500);
}

document.addEventListener('DOMContentLoaded', load);
</script>
</body>
</html>
