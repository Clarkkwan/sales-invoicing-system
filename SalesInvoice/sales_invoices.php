<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Invoices - Sales &amp; Invoicing System</title>
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
    --accent-glow: rgba(37,99,235,0.18);
    --accent-dim: rgba(37,99,235,0.08);
    --border: #e2e8f0;
    --border-med: #cbd5e1;
    --text: #334155;
    --text-hi: #0f172a;
    --text-3: #64748b;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --heat-bg: #fef2f2;
    --ok: #16a34a;
    --sh-xs: 0 1px 2px rgba(15,23,42,.05);
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
    background: var(--accent-dim);
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
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
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
    color: var(--text-hi);
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

#search-invoices {
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-hi);
    padding: 8px 10px 8px 32px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    width: 260px;
    transition: all 0.15s;
    box-shadow: var(--sh-xs);
}

#search-invoices:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

#search-invoices::placeholder {
    color: var(--text-lo);
}

/* Summary chips */
.summary-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.chip {
    padding: 4px 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    font-size: 11px;
    font-weight: 600;
    color: var(--text-3);
    font-family: 'Roboto Mono', monospace;
}

.chip span {
    color: var(--accent);
    font-weight: 700;
    margin-left: 6px;
}

/* Table */
.table-wrap {
    flex: 1;
    overflow-y: auto;
}

.table-wrap::-webkit-scrollbar {
    width: 6px;
}

.table-wrap::-webkit-scrollbar-track {
    background: var(--bg);
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
    transition: background 0.1s;
    cursor: pointer;
}

tbody tr:hover td {
    background: var(--accent-light);
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

.badge-individual {
    color: #475569;
    border-color: #e2e8f0;
    background: #f8fafc;
}

.badge-corporate {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #eff6ff;
}

.inv-no {
    font-family: 'Roboto Mono', monospace;
    font-size: 12px;
    font-weight: 700;
    color: var(--accent);
    letter-spacing: 0.5px;
}

.td-total {
    font-family: 'Roboto Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-hi);
}

.td-sub {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    color: var(--text-3);
}

.btn-view {
    padding: 5px 12px;
    background: var(--accent);
    border: none;
    color: white;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: background 0.12s;
}

.btn-view:hover {
    background: #1d4ed8;
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
    opacity: 0.2;
}

.empty-state .e-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-3);
}

.empty-state .e-sub {
    font-size: 11px;
    color: var(--text-lo);
    margin-top: 4px;
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

    <a href="dashboard.php" class="nav-item" style="text-decoration:none">
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
        <a class="nav-sub-item" href="products.php?platform=PS5">PS5</a>
        <a class="nav-sub-item" href="products.php?platform=Switch">Switch</a>
        <a class="nav-sub-item" href="products.php?platform=Xbox">Xbox</a>
        <a class="nav-sub-item" href="products.php?platform=PC">PC</a>
        <a class="nav-sub-item" href="products.php?platform=GiftCard">Gift Cards</a>
    </div>

    <a href="sales_invoices.php" class="nav-item active" style="text-decoration:none">
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
        <div class="toolbar-top">
            <span class="page-title">Sales Invoices</span>
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="search-invoices" placeholder="Search invoice no, client...">
            </div>
        </div>
        <div class="summary-chips">
            <div class="chip">Total Invoices<span id="chip-count">—</span></div>
            <div class="chip">Total Revenue<span id="chip-revenue">—</span></div>
            <div class="chip">Total VAT<span id="chip-vat">—</span></div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Client</th>
                    <th style="width:90px">Type</th>
                    <th style="width:60px;text-align:center">Items</th>
                    <th style="width:120px;text-align:right">Subtotal</th>
                    <th style="width:110px;text-align:right">VAT (12%)</th>
                    <th style="width:120px;text-align:right">Total Due</th>
                    <th style="width:110px">Date</th>
                    <th style="width:80px;text-align:center">View</th>
                </tr>
            </thead>
            <tbody id="invoices-tbody">
                <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-lo)">Loading invoices...</td></tr>
            </tbody>
        </table>
        <div id="empty-state" class="empty-state" style="display:none">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <div class="e-title">No invoices yet</div>
            <div class="e-sub">Finalize a sale from the Point of Sale to generate your first invoice</div>
        </div>
    </div>
</div>

</div>

<script>
const fmtPHP = n => '₱' + parseFloat(n).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

let allSales = [];
let searchQuery = '';

function renderInvoices() {
    const q      = searchQuery.toLowerCase();
    const tbody  = document.getElementById('invoices-tbody');
    const empty  = document.getElementById('empty-state');

    const filtered = allSales.filter(s =>
        !q
        || s.invoice_no.toLowerCase().includes(q)
        || s.client_name.toLowerCase().includes(q)
    );

    if (allSales.length === 0) {
        tbody.innerHTML = '';
        empty.style.display = 'flex';
        return;
    }

    empty.style.display = 'none';

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-lo)">No invoices match your search</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(s => {
        const date = new Date(s.date_created).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
        const time = new Date(s.date_created).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
        const ewtRow = parseFloat(s.ewt_amount) > 0
            ? `<div style="font-size:10px;color:var(--heat);font-family:'Roboto Mono',monospace">EWT −${fmtPHP(s.ewt_amount)}</div>`
            : '';
        return `<tr onclick="viewInvoice('${esc(s.invoice_no)}')" title="Click to view invoice">
            <td><div class="inv-no">${esc(s.invoice_no)}</div></td>
            <td style="font-weight:600;color:var(--text-hi);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(s.client_name)}</td>
            <td><span class="badge badge-${esc(s.client_type)}">${esc(s.client_type)}</span></td>
            <td style="text-align:center;font-family:'Roboto Mono',monospace;font-weight:600">${s.item_count}</td>
            <td style="text-align:right"><div class="td-sub">${fmtPHP(s.subtotal)}</div></td>
            <td style="text-align:right"><div class="td-sub">${fmtPHP(s.vat_amount)}</div>${ewtRow}</td>
            <td style="text-align:right"><div class="td-total">${fmtPHP(s.total_amount)}</div></td>
            <td style="font-size:11px;color:var(--text-3)"><div>${date}</div><div style="color:var(--text-lo)">${time}</div></td>
            <td style="text-align:center">
                <a class="btn-view" href="invoice.php?no=${encodeURIComponent(s.invoice_no)}" target="_blank" onclick="event.stopPropagation()">
                    View
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                </a>
            </td>
        </tr>`;
    }).join('');
}

function viewInvoice(invoiceNo) {
    window.open(`invoice.php?no=${encodeURIComponent(invoiceNo)}`, '_blank');
}

function updateSummary() {
    const total   = allSales.reduce((s, r) => s + parseFloat(r.total_amount), 0);
    const vat     = allSales.reduce((s, r) => s + parseFloat(r.vat_amount), 0);
    document.getElementById('chip-count').textContent   = allSales.length;
    document.getElementById('chip-revenue').textContent = fmtPHP(total);
    document.getElementById('chip-vat').textContent     = fmtPHP(vat);
}

async function loadSales() {
    try {
        const res  = await fetch('api/sales.php?limit=500');
        allSales   = await res.json();
        renderInvoices();
        updateSummary();
    } catch (e) {
        document.getElementById('invoices-tbody').innerHTML =
            `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--heat)">Failed to load invoices: ${esc(e.message)}</td></tr>`;
    }
}

let searchDeb;
document.getElementById('search-invoices').addEventListener('input', function() {
    clearTimeout(searchDeb);
    searchDeb = setTimeout(() => { searchQuery = this.value; renderInvoices(); }, 140);
});

function toggleProductsNav() {
    const toggle = document.getElementById('nav-products-toggle');
    const sub    = document.getElementById('nav-products-sub');
    const open   = toggle.classList.toggle('expanded');
    sub.classList.toggle('open', open);
    sub.style.maxHeight = open ? sub.scrollHeight + 'px' : '0';
}

document.addEventListener('DOMContentLoaded', loadSales);
</script>
</body>
</html>
