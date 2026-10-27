<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
// Admins only
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Users - Sales &amp; Invoicing System</title>
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
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 14px 20px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--sh-sm);
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

#search-users {
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

#search-users:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

#search-users::placeholder {
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
    transition: all .15s;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-add:hover {
    background: #1d4ed8;
    box-shadow: 0 4px 10px rgba(37,99,235,.35);
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
}

tbody tr:hover td {
    background: var(--accent-light);
}

.td-mono {
    font-family: 'Roboto Mono', monospace;
    font-size: 11px;
    color: var(--text-3);
}

.badge-role {
    display: inline-block;
    padding: 2px 8px;
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    border: 1px solid;
}

.badge-admin {
    color: #7c3aed;
    border-color: #ddd6fe;
    background: #f5f3ff;
}

.badge-cashier {
    color: #475569;
    border-color: #e2e8f0;
    background: #f8fafc;
}

.badge-active {
    color: #16a34a;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.badge-inactive {
    color: #94a3b8;
    border-color: #e2e8f0;
    background: #f8fafc;
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
    transition: all .15s;
}

.btn-icon svg {
    width: 13px;
    height: 13px;
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

.empty-row td {
    text-align: center;
    padding: 60px 20px;
    background: var(--surface);
}

.empty-lbl {
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
    max-width: 520px;
    box-shadow: var(--sh-lg);
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
    color: var(--text-3);
}

.form-inp, .form-sel {
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

.form-inp:focus, .form-sel:focus {
    border-color: var(--accent);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--accent-glow);
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

.form-err {
    font-size: 11px;
    color: var(--heat);
    font-family: 'Roboto Mono', monospace;
    min-height: 16px;
    padding: 0 22px 6px;
}

.pw-hint {
    font-size: 10px;
    color: var(--text-lo);
    margin-top: 3px;
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
    transition: all .15s;
    font-family: 'Inter', sans-serif;
}

.btn-delete:hover {
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
    <a href="users.php" class="nav-item active" style="text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        System Users
    </a>

    <div class="nav-footer">
        <a href="logout.php"
           style="display:block;padding:6px 10px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-align:center;text-decoration:none;color:var(--heat);border:1px solid #fecaca;transition:all .12s"
           onmouseover="this.style.background='var(--heat-bg)'"
           onmouseout="this.style.background='transparent'">Logout</a>
    </div>
</nav>

<div id="main-panel">
    <div class="page-toolbar">
        <span class="page-title">System Users</span>
        <div style="display:flex;align-items:center;gap:10px">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="search-users" placeholder="Search name, username...">
            </div>
            <button class="btn-add" onclick="openAddModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add User
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Added</th>
                    <th style="width:90px;text-align:center">Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody"></tbody>
        </table>
    </div>
</div>

</div>
<div id="toast"></div>

<!-- Add / edit modal -->
<div class="modal-bg" id="modal-form">
    <div class="modal-box">
        <div class="modal-title" id="form-modal-title">Add User</div>
        <input type="hidden" id="form-id">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Full Name *</label>
                <input class="form-inp" id="form-name" placeholder="e.g. Maria Santos" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input class="form-inp" id="form-username" placeholder="e.g. msantos" maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select class="form-sel" id="form-role">
                    <option value="cashier">Cashier</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-inp" id="form-email" type="email" placeholder="email@example.com" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input class="form-inp" id="form-phone" placeholder="+63 9XX XXX XXXX" maxlength="50">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Password <span id="pw-required-label" style="color:var(--heat)">*</span></label>
                <input class="form-inp" id="form-password" type="password" placeholder="Enter password" autocomplete="new-password">
                <div class="pw-hint" id="pw-hint">Leave blank to keep existing password when editing.</div>
            </div>
        </div>
        <div class="form-err" id="form-err"></div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-form')">Cancel</button>
            <button class="btn-save" id="btn-save" onclick="saveUser()">Save User</button>
        </div>
    </div>
</div>

<!-- Delete modal -->
<div class="modal-bg" id="modal-delete">
    <div class="modal-box" style="max-width:400px">
        <div class="modal-title danger">Confirm Delete</div>
        <div style="padding:20px 22px">
            <p style="color:var(--text-2);line-height:1.6">
                Delete user <strong id="del-name" style="color:var(--text)"></strong>?
                This cannot be undone.
            </p>
        </div>
        <input type="hidden" id="del-id">
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('modal-delete')">Cancel</button>
            <button class="btn-delete" id="btn-del" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let users = [];
let searchQ = '';

function renderTable() {
    const q = searchQ.toLowerCase();
    const tbody = document.getElementById('users-tbody');
    const filtered = users.filter(u =>
        !q
        || u.name.toLowerCase().includes(q)
        || u.username.toLowerCase().includes(q)
        || (u.email || '').toLowerCase().includes(q)
    );

    if (users.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7"><div class="empty-lbl">No users yet</div></td></tr>`;
        return;
    }
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="7"><div class="empty-lbl">No users match your search</div></td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(u => {
        const date = u.created_at
            ? new Date(u.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
            : '—';
        return `<tr>
            <td style="font-weight:600;color:var(--text)">${esc(u.name)}</td>
            <td class="td-mono">${esc(u.username)}</td>
            <td class="td-mono" style="font-size:11px">${esc(u.email||'—')}</td>
            <td class="td-mono">${esc(u.phone||'—')}</td>
            <td><span class="badge-role badge-${u.role}">${esc(u.role)}</span></td>
            <td style="font-size:11px;color:var(--text-3)">${date}</td>
            <td style="text-align:center">
                <div style="display:inline-flex;gap:5px">
                    <button class="btn-icon edit" title="Edit" onclick="openEditModal(${u.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn-icon del" title="Delete" onclick="openDeleteModal(${u.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => {
    if (e.target === m) closeModal(m.id);
}));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach(m => closeModal(m.id));
});

function resetForm(isEdit = false) {
    ['form-id', 'form-name', 'form-username', 'form-email', 'form-phone', 'form-password']
        .forEach(id => document.getElementById(id).value = '');
    document.getElementById('form-role').value = 'cashier';
    document.getElementById('form-err').textContent = '';
    document.getElementById('pw-hint').style.display = isEdit ? 'block' : 'none';
    document.getElementById('pw-required-label').style.display = isEdit ? 'none' : 'inline';
    const btn = document.getElementById('btn-save');
    btn.disabled = false;
    btn.textContent = 'Save User';
}

function openAddModal() {
    resetForm(false);
    document.getElementById('form-modal-title').textContent = '// Add User';
    openModal('modal-form');
    setTimeout(() => document.getElementById('form-name').focus(), 80);
}

function openEditModal(id) {
    const u = users.find(x => x.id == id);
    if (!u) return;
    resetForm(true);
    document.getElementById('form-modal-title').textContent = '// Edit User';
    document.getElementById('form-id').value       = u.id;
    document.getElementById('form-name').value     = u.name;
    document.getElementById('form-username').value = u.username;
    document.getElementById('form-email').value    = u.email || '';
    document.getElementById('form-phone').value    = u.phone || '';
    document.getElementById('form-role').value     = u.role;
    openModal('modal-form');
    setTimeout(() => document.getElementById('form-name').focus(), 80);
}

async function saveUser() {
    const btn = document.getElementById('btn-save');
    const err = document.getElementById('form-err');
    const payload = {
        action:   'save',
        id:       document.getElementById('form-id').value ? parseInt(document.getElementById('form-id').value) : 0,
        name:     document.getElementById('form-name').value.trim(),
        username: document.getElementById('form-username').value.trim(),
        email:    document.getElementById('form-email').value.trim(),
        phone:    document.getElementById('form-phone').value.trim(),
        role:     document.getElementById('form-role').value,
        password: document.getElementById('form-password').value,
    };
    if (!payload.name) {
        err.textContent = 'Name is required';
        return;
    }
    if (!payload.username) {
        err.textContent = 'Username is required';
        return;
    }

    err.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Saving...';
    try {
        const res  = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-form');
            showToast(payload.id ? 'User updated' : 'User added', 'ok');
            await refresh();
        } else {
            err.textContent = data.error || 'Save failed';
        }
    } catch (e) {
        err.textContent = 'Network error: ' + e.message;
    }
    btn.disabled = false;
    btn.textContent = 'Save User';
}

function openDeleteModal(id) {
    const u = users.find(x => x.id == id);
    if (!u) return;
    document.getElementById('del-id').value = id;
    document.getElementById('del-name').textContent = u.name;
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
        const res  = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id }),
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-delete');
            showToast('User deleted', 'ok');
            await refresh();
        } else {
            showToast(data.error || 'Delete failed', 'err');
            closeModal('modal-delete');
        }
    } catch (e) {
        showToast('Network error: ' + e.message, 'err');
    }
    btn.disabled = false;
    btn.textContent = 'Delete';
}

async function refresh() {
    try {
        const r = await fetch('api/users.php');
        users = await r.json();
        renderTable();
    } catch (e) {
        // Ignore; keep the current table
    }
}

let searchDeb;
document.getElementById('search-users').addEventListener('input', function() {
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
    toastTimer = setTimeout(() => t.className = '', 3000);
}

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

document.addEventListener('DOMContentLoaded', refresh);
</script>
</body>
</html>
