<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$stmt = $pdo->query("
    SELECT id, title, platform, format, sku, stock, price
    FROM products
    ORDER BY FIELD(platform, 'PC', 'PS5', 'Switch', 'Xbox', 'GiftCard', 'Other'), title
");
$products    = $stmt->fetchAll();
$nextInvoice = getNextInvoiceNo($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Point of Sale - Sales &amp; Invoicing System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Colors and shadows */
:root {
    --bg: #eef2f7;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --surface-3: #f1f5f9;
    --accent: #2563eb;
    --accent-dark: #1d4ed8;
    --accent-light: #eff6ff;
    --accent-ring: rgba(37,99,235,0.18);
    --border: #e2e8f0;
    --border-med: #cbd5e1;
    --text: #0f172a;
    --text-2: #334155;
    --text-3: #64748b;
    --text-lo: #94a3b8;
    --heat: #dc2626;
    --heat-bg: #fef2f2;
    --heat-ring: #fecaca;
    --ok: #16a34a;
    --warn: #d97706;
    --warn-bg: #fffbeb;
    --sh-xs: 0 1px 2px rgba(15,23,42,.05);
    --sh-lg: 0 10px 15px -3px rgba(15,23,42,.08), 0 4px 6px -4px rgba(15,23,42,.04);
}

/* Reset */
*, *::before, *::after {
    box-sizing: border-box;
    border-radius: 0 !important;
    margin: 0;
    padding: 0;
}

html, body {
    height: 100%;
    overflow: hidden;
    background: #eef2f7;
    color: #0f172a;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
}

/* App shell */
#app {
    display: grid;
    grid-template-columns: 230px 1fr 390px;
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
    transition: all 0.12s;
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
    gap: 0;
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
    flex-shrink: 0;
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

.nav-sub-item.active {
    color: var(--accent);
    font-weight: 600;
    border-left-color: var(--accent);
    background: var(--accent-light);
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

/* Product grid */
#grid-panel {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--bg);
}

.grid-toolbar {
    flex-shrink: 0;
    padding: 14px 20px 12px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}

.toolbar-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.page-heading {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    white-space: nowrap;
}

.search-wrap {
    flex: 1;
    position: relative;
}

.search-wrap svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    color: var(--text-lo);
    pointer-events: none;
}

#search-input {
    width: 100%;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 10px 8px 34px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    transition: all 0.15s;
    box-shadow: var(--sh-xs);
}

#search-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-ring);
    background: var(--surface);
}

#search-input::placeholder {
    color: var(--text-lo);
}

.filter-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.ftab {
    padding: 5px 14px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-3);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
    font-family: 'Inter', sans-serif;
    box-shadow: var(--sh-xs);
}

.ftab:hover {
    background: var(--surface-2);
    border-color: var(--border-med);
    color: var(--text);
}

.ftab.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
    box-shadow: 0 1px 3px rgba(37,99,235,.3);
}

#products-grid {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    grid-auto-rows: 216px;
    gap: 12px;
    align-content: start;
}

#products-grid::-webkit-scrollbar {
    width: 6px;
}

#products-grid::-webkit-scrollbar-track {
    background: #eef2f7;
}

#products-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
}

/* Product card */
.pcard {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    display: flex;
    flex-direction: column;
    height: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
    transition: box-shadow 0.15s, border-color 0.15s, transform 0.12s;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
}

.pcard:hover {
    box-shadow: 0 4px 12px rgba(37,99,235,.15);
    border-color: #2563eb;
    transform: translateY(-2px);
}

.pcard:active {
    transform: translateY(0);
}

.pcard-stripe {
    height: 4px;
    flex-shrink: 0;
}

.pcard-stripe-PS5 {
    background: #0050d4;
}

.pcard-stripe-Switch {
    background: #e4000f;
}

.pcard-stripe-Xbox {
    background: #107c10;
}

.pcard-stripe-PC {
    background: #6366f1;
}

.pcard-stripe-GiftCard {
    background: #f59e0b;
}

.pcard-stripe-Other {
    background: #94a3b8;
}

.pcard.heat-check {
    border-color: #fca5a5 !important;
    box-shadow: 0 0 0 2px #fecaca !important;
}

.pcard.heat-check .pcard-stripe {
    background: #dc2626 !important;
    animation: heat-pulse 1.3s ease-in-out infinite;
}

@keyframes heat-pulse {
    0%, 100% {
        opacity: 1;
    }

    50% {
        opacity: .3;
    }
}

.heat-badge {
    position: absolute;
    top: 12px;
    right: 8px;
    font-size: 9px;
    font-weight: 700;
    font-family: 'Roboto Mono', monospace;
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 1px 5px;
    letter-spacing: .5px;
}

.pcard-body {
    padding: 10px 12px 8px;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.pcard-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 7px;
    flex-shrink: 0;
}

/* Platform badges */
.platform-badge {
    display: inline-block;
    padding: 2px 6px;
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

.badge-GiftCard {
    color: #b45309;
    border-color: #fde68a;
    background: #fffbeb;
}

.badge-Other {
    color: #475569;
    border-color: #e2e8f0;
    background: #f8fafc;
}

/* Stock chips */
.stock-chip {
    font-family: 'Roboto Mono', monospace;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 6px;
    border: 1px solid;
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
    color: #64748b;
    border-color: #e2e8f0;
    background: #f8fafc;
}

.pc-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    margin-bottom: 4px;
    flex-shrink: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.pc-sku {
    font-family: 'Roboto Mono', monospace;
    font-size: 10px;
    color: #64748b;
    flex-shrink: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pc-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 7px;
    border-top: 1px solid #e2e8f0;
    margin-top: auto;
    flex-shrink: 0;
}

.pc-price {
    font-family: 'Roboto Mono', monospace;
    font-size: 14px;
    font-weight: 700;
    color: #2563eb;
}

.pc-format {
    font-size: 9px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.in-cart-tag {
    margin: 0;
    padding: 3px 12px;
    text-align: center;
    font-size: 10px;
    font-weight: 600;
    font-family: 'Roboto Mono', monospace;
    color: #16a34a;
    background: #f0fdf4;
    border-top: 1px solid #bbf7d0;
    flex-shrink: 0;
}

.pc-add-btn {
    display: block;
    width: 100%;
    padding: 9px 12px;
    background: #2563eb;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-align: center;
    font-family: 'Inter', sans-serif;
    flex-shrink: 0;
    user-select: none;
    transition: background 0.12s;
}

.pcard:hover .pc-add-btn:not(.pc-add-btn-disabled) {
    background: #1d4ed8;
}

.pc-add-btn.btn-in-cart {
    background: #16a34a;
}

.pcard:hover .pc-add-btn.btn-in-cart {
    background: #15803d;
}

.pc-add-btn-disabled {
    background: #f1f5f9 !important;
    color: #94a3b8 !important;
    cursor: default;
}

/* Invoice panel */
#invoice-panel {
    background: var(--surface);
    border-left: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: -3px 0 10px rgba(15,23,42,.06);
}

.inv-panel-header {
    padding: 15px 18px 13px;
    border-bottom: 1px solid var(--border);
    background: var(--accent-light);
    border-top: 3px solid var(--accent);
    flex-shrink: 0;
}

.inv-panel-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
    margin-bottom: 4px;
}

.inv-panel-no {
    font-family: 'Roboto Mono', monospace;
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: 1px;
    line-height: 1;
}

.inv-panel-date {
    font-size: 11px;
    color: var(--text-3);
    margin-top: 3px;
}

.inv-client-section {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
    background: var(--surface-2);
}

.field-lbl {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-3);
    margin-bottom: 5px;
}

.field-inp {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 8px 10px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    outline: none;
    transition: all 0.15s;
    box-shadow: var(--sh-xs);
}

.field-inp:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-ring);
}

.field-inp::placeholder {
    color: var(--text-lo);
}

.client-type-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: 8px;
}

.ctype-btn {
    padding: 7px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-3);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.12s;
    font-family: 'Inter', sans-serif;
    box-shadow: var(--sh-xs);
}

.ctype-btn:hover {
    background: var(--surface-2);
    color: var(--text);
}

.ctype-btn.active {
    background: var(--accent-light);
    border-color: var(--accent);
    color: var(--accent);
    font-weight: 700;
}

.ewt-row {
    display: none;
    align-items: center;
    gap: 5px;
    margin-top: 8px;
}

.ewt-row.show {
    display: flex;
}

.ewt-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-3);
    white-space: nowrap;
    margin-right: 2px;
}

.ewt-btn {
    padding: 5px 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-3);
    font-size: 11px;
    font-weight: 600;
    font-family: 'Roboto Mono', monospace;
    cursor: pointer;
    transition: all 0.12s;
    box-shadow: var(--sh-xs);
}

.ewt-btn:hover {
    background: var(--surface-2);
}

.ewt-btn.active {
    border-color: var(--warn);
    color: var(--warn);
    background: var(--warn-bg);
}

/* Line items */
#invoice-items {
    flex: 1;
    overflow-y: auto;
    padding: 4px 0;
}

#invoice-items::-webkit-scrollbar {
    width: 4px;
}

#invoice-items::-webkit-scrollbar-thumb {
    background: var(--border-med);
}

.inv-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-lo);
    text-align: center;
    padding: 24px;
}

.inv-empty svg {
    margin-bottom: 14px;
    opacity: 0.25;
}

.inv-empty .e-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-3);
}

.inv-empty .e-sub {
    font-size: 11px;
    margin-top: 4px;
}

.line-item {
    border-bottom: 1px solid var(--border);
    padding: 10px 18px;
    transition: background 0.1s;
}

.line-item:hover {
    background: var(--surface-2);
}

.li-title {
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
    line-height: 1.25;
    margin-bottom: 2px;
}

.li-meta {
    font-family: 'Roboto Mono', monospace;
    font-size: 10px;
    color: var(--text-lo);
    margin-bottom: 8px;
}

.li-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.qty-wrap {
    display: flex;
    align-items: center;
}

.qty-btn {
    width: 26px;
    height: 26px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    color: var(--text-3);
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.1s;
}

.qty-btn:hover {
    background: var(--accent-light);
    border-color: var(--accent);
    color: var(--accent);
}

.qty-num {
    width: 36px;
    height: 26px;
    background: var(--surface);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    color: var(--text);
    font-family: 'Roboto Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

.li-total {
    font-family: 'Roboto Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
}

.li-del {
    background: transparent;
    border: none;
    color: var(--text-lo);
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.1s;
    font-size: 14px;
    padding: 0;
}

.li-del:hover {
    color: var(--heat);
    background: var(--heat-bg);
}

/* Tax */
#tax-section {
    padding: 12px 18px;
    border-top: 2px solid var(--border);
    background: var(--surface-2);
    flex-shrink: 0;
}

.tax-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
}

.tx-lbl {
    font-size: 11px;
    color: var(--text-3);
}

.tx-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 12px;
    color: var(--text);
    font-weight: 500;
}

.tx-val.neg {
    color: var(--heat);
}

.total-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    margin-top: 8px;
    border-top: 2px solid var(--accent);
}

.total-lbl {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
}

.total-val {
    font-family: 'Roboto Mono', monospace;
    font-size: 20px;
    font-weight: 700;
    color: var(--accent);
}

/* Actions */
#inv-actions {
    padding: 12px 18px;
    border-top: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex-shrink: 0;
}

.btn-finalize {
    width: 100%;
    padding: 12px;
    background: var(--accent);
    border: none;
    color: white;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.18s;
    font-family: 'Inter', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 2px 4px rgba(37,99,235,.25);
}

.btn-finalize:hover:not(:disabled) {
    background: var(--accent-dark);
    box-shadow: 0 4px 10px rgba(37,99,235,.35);
    transform: translateY(-1px);
}

.btn-finalize:active:not(:disabled) {
    transform: translateY(0);
}

.btn-finalize:disabled {
    background: var(--surface-3);
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: not-allowed;
    box-shadow: none;
}

.btn-clear-cart {
    width: 100%;
    padding: 7px;
    background: transparent;
    border: 1px solid var(--heat-ring);
    color: var(--heat);
    font-weight: 600;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.12s;
    font-family: 'Inter', sans-serif;
    opacity: 0.7;
}

.btn-clear-cart:hover {
    background: var(--heat-bg);
    border-color: var(--heat);
    opacity: 1;
}

/* Toast */
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
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
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

#toast.toast-warn {
    background: var(--warn);
}

/* Modals */
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
    max-width: 500px;
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

.modal-body {
    padding: 20px 22px;
}

.modal-footer {
    display: flex;
    gap: 10px;
    padding: 14px 22px;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
}

.btn-modal-cancel {
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
    transition: all 0.12s;
    font-family: 'Inter', sans-serif;
}

.btn-modal-cancel:hover {
    background: var(--surface-2);
    border-color: var(--border-med);
    color: var(--text);
}

.btn-modal-confirm {
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

.btn-modal-confirm:hover:not(:disabled) {
    background: var(--accent-dark);
}

.btn-modal-confirm:disabled {
    background: var(--surface-3);
    border: 1px solid var(--border);
    color: var(--text-lo);
    cursor: not-allowed;
    box-shadow: none;
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

/* Print */
@media print {
    body {
        overflow: auto !important;
        background: white !important;
    }

    #app {
        display: block !important;
        height: auto !important;
    }

    #nav-panel, #grid-panel, #toast, .modal-bg {
        display: none !important;
    }

    #invoice-panel {
        display: block !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: none !important;
        overflow: visible !important;
    }
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
    <div class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Point of Sale
    </div>
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
           style="display:block;padding:6px 10px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-align:center;text-decoration:none;color:var(--heat);border:1px solid var(--heat-ring);transition:all 0.12s"
           onmouseover="this.style.background='var(--heat-bg)'"
           onmouseout="this.style.background='transparent'">Logout</a>
    </div>
</nav>


<!-- Product grid -->
<main id="grid-panel">
    <div class="grid-toolbar">
        <div class="toolbar-top">
            <span class="page-heading">Product Catalog</span>
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="search-input" placeholder="Search title, platform, SKU...">
            </div>
        </div>
        <div class="filter-tabs">
            <button class="ftab active" data-platform="all"      onclick="setFilter('all')">All</button>
            <button class="ftab"        data-platform="PC"       onclick="setFilter('PC')">PC</button>
            <button class="ftab"        data-platform="PS5"      onclick="setFilter('PS5')">PS5</button>
            <button class="ftab"        data-platform="Switch"   onclick="setFilter('Switch')">Switch</button>
            <button class="ftab"        data-platform="Xbox"     onclick="setFilter('Xbox')">Xbox</button>
            <button class="ftab"        data-platform="GiftCard" onclick="setFilter('GiftCard')">Gift Cards</button>
        </div>
    </div>
    <div id="products-grid"></div>
</main>

<aside id="invoice-panel">
    <div class="inv-panel-header">
        <div class="inv-panel-label">Service Invoice Preview</div>
        <div class="inv-panel-no" id="inv-no-display"><?= htmlspecialchars($nextInvoice) ?></div>
        <div class="inv-panel-date" id="inv-date-display"></div>
    </div>

    <div class="inv-client-section">
        <div class="field-lbl">Client / Billed To</div>
        <input type="text" class="field-inp" id="client-name" placeholder="Enter client name..." maxlength="200">
        <div class="client-type-row">
            <button class="ctype-btn active" data-type="individual" onclick="setClientType('individual')">Individual</button>
            <button class="ctype-btn"        data-type="corporate"  onclick="setClientType('corporate')">Corporate</button>
        </div>
        <div class="ewt-row" id="ewt-row">
            <span class="ewt-label">EWT:</span>
            <button class="ewt-btn" data-rate="0" onclick="setEwtRate(0)">None</button>
            <button class="ewt-btn" data-rate="1" onclick="setEwtRate(1)">1%</button>
            <button class="ewt-btn" data-rate="2" onclick="setEwtRate(2)">2%</button>
        </div>
    </div>

    <div id="invoice-items"></div>

    <div id="tax-section">
        <div class="tax-row">
            <span class="tx-lbl">Subtotal (Gross)</span>
            <span class="tx-val" id="t-subtotal">₱0.00</span>
        </div>
        <div class="tax-row">
            <span class="tx-lbl">VAT (12%) Exclusive</span>
            <span class="tx-val" id="t-vat">₱0.00</span>
        </div>
        <div class="tax-row" id="ewt-tax-row" style="display:none">
            <span class="tx-lbl" id="ewt-tax-label">EWT</span>
            <span class="tx-val neg" id="t-ewt">—</span>
        </div>
        <div class="total-bar">
            <span class="total-lbl">Total Due</span>
            <span class="total-val" id="t-total">₱0.00</span>
        </div>
    </div>

    <div id="inv-actions">
        <button class="btn-finalize" id="btn-finalize" disabled onclick="openFinalizeModal()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Finalize &amp; Generate Invoice
        </button>
        <button class="btn-clear-cart" onclick="clearCart()">Clear Invoice</button>
    </div>
</aside>

</div>

<div id="toast"></div>

<!-- Modal: finalize -->
<div class="modal-bg" id="modal-finalize">
    <div class="modal-box">
        <div class="modal-title">Confirm Invoice</div>
        <div class="modal-body" id="modal-finalize-body"></div>
        <div class="modal-footer">
            <button class="btn-modal-cancel"  onclick="closeModal('modal-finalize')">Cancel</button>
            <button class="btn-modal-confirm" id="btn-confirm" onclick="confirmFinalize()">Confirm &amp; Generate</button>
        </div>
    </div>
</div>

<script>
let products      = <?= json_encode(array_values($products), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
let cart          = <?= json_encode(array_values($_SESSION['cart']), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
let clientType    = 'individual';
let ewtRate       = 0;
let currentFilter = 'all';
let searchQuery   = '';

const fmtPHP = n => '₱' + parseFloat(n).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function calcTotals() {
    const subtotal  = cart.reduce((s, i) => s + parseFloat(i.price) * i.quantity, 0);
    const vatAmount = subtotal * 0.12;
    const ewtAmount = subtotal * (ewtRate / 100);
    const total     = (subtotal + vatAmount) - ewtAmount;
    return { subtotal, vatAmount, ewtAmount, total };
}

function renderProducts() {
    const grid     = document.getElementById('products-grid');
    const filtered = products.filter(p => {
        const q = searchQuery.toLowerCase();
        const matchesSearch = !q
            || p.title.toLowerCase().includes(q)
            || p.platform.toLowerCase().includes(q)
            || p.sku.toLowerCase().includes(q);
        const matchesPlatform = currentFilter === 'all' || p.platform === currentFilter;
        return matchesSearch && matchesPlatform;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;padding:60px 20px;text-align:center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border-med)" stroke-width="1.5" style="margin-bottom:12px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <div style="font-size:12px;color:var(--text-3);font-weight:600">No products found</div>
            <div style="font-size:11px;color:var(--text-lo);margin-top:4px">Try a different search or filter</div>
        </div>`;
        return;
    }

    grid.innerHTML = filtered.map(p => {
        const cartItem   = cart.find(c => c.product_id == p.id);
        const inCart     = cartItem ? cartItem.quantity : 0;
        const isLow      = p.stock > 0 && p.stock < 5;
        const isOut      = p.stock <= 0;
        const stockClass = isOut ? 'stock-zero' : isLow ? 'stock-low' : 'stock-ok';
        const stockLabel = isOut ? 'OUT' : `QTY ${p.stock}`;
        const btnClass   = inCart > 0 ? ' btn-in-cart' : '';
        const btnLabel   = isOut ? 'Out of Stock' : inCart > 0 ? `✓ Add More (${inCart})` : '+ Add to Invoice';

        const cardOnclick = isOut ? '' : ` onclick="addToCart(${p.id})"`;
        const cardCursor  = isOut ? ' style="cursor:default;opacity:0.6"' : '';
        return `<div class="pcard${isLow ? ' heat-check' : ''}"${cardOnclick}${cardCursor}>`
            + '<div class="pcard-stripe pcard-stripe-' + esc(p.platform) + '"></div>'
            + '<div class="pcard-body">'
            +   '<div class="pcard-head">'
            +     '<span class="platform-badge badge-' + esc(p.platform) + '">' + esc(p.platform) + '</span>'
            +     '<span class="stock-chip ' + stockClass + '">' + stockLabel + '</span>'
            +   '</div>'
            +   (isLow ? '<div class="heat-badge">⚠ LOW</div>' : '')
            +   '<div class="pc-title">' + esc(p.title) + '</div>'
            +   '<div class="pc-sku">' + esc(p.sku) + '</div>'
            +   '<div class="pc-footer">'
            +     '<span class="pc-price">' + fmtPHP(p.price) + '</span>'
            +     '<span class="pc-format">' + esc(p.format) + '</span>'
            +   '</div>'
            + '</div>'
            + (inCart > 0 ? '<div class="in-cart-tag">✓ In Invoice: ×' + inCart + '</div>' : '')
            + '<div class="pc-add-btn' + btnClass + (isOut ? ' pc-add-btn-disabled' : '') + '">' + btnLabel + '</div>'
            + '</div>';
    }).join('');
}

function renderInvoice() {
    const container = document.getElementById('invoice-items');
    const btn       = document.getElementById('btn-finalize');

    if (cart.length === 0) {
        container.innerHTML = `<div class="inv-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <rect x="3" y="3" width="18" height="18"/><line x1="9" y1="9" x2="15" y2="9"/>
                <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="15" x2="13" y2="15"/>
            </svg>
            <div class="e-title">Invoice Empty</div>
            <div class="e-sub">Select products from the catalog</div>
        </div>`;
        btn.disabled = true;
        updateTaxDisplay(0, 0, 0, 0);
        return;
    }

    container.innerHTML = cart.map(item => `
        <div class="line-item">
            <div class="li-title">${esc(item.title)}</div>
            <div class="li-meta">${esc(item.sku)} · ${esc(item.platform)}</div>
            <div class="li-controls">
                <div class="qty-wrap">
                    <button class="qty-btn" onclick="updateQty(${item.product_id}, -1)">−</button>
                    <div class="qty-num">${item.quantity}</div>
                    <button class="qty-btn" onclick="updateQty(${item.product_id}, +1)">+</button>
                </div>
                <span class="li-total">${fmtPHP(item.price * item.quantity)}</span>
                <button class="li-del" onclick="removeFromCart(${item.product_id})" title="Remove">✕</button>
            </div>
        </div>`).join('');

    const { subtotal, vatAmount, ewtAmount, total } = calcTotals();
    updateTaxDisplay(subtotal, vatAmount, ewtAmount, total);
    btn.disabled = false;
}

function updateTaxDisplay(subtotal, vat, ewt, total) {
    document.getElementById('t-subtotal').textContent = fmtPHP(subtotal);
    document.getElementById('t-vat').textContent      = fmtPHP(vat);
    document.getElementById('t-total').textContent    = fmtPHP(total);
    const ewtRow = document.getElementById('ewt-tax-row');
    if (ewtRate > 0 && ewt > 0) {
        ewtRow.style.display = 'flex';
        document.getElementById('ewt-tax-label').textContent = `EWT (${ewtRate}%)`;
        document.getElementById('t-ewt').textContent = `-${fmtPHP(ewt)}`;
    } else {
        ewtRow.style.display = 'none';
    }
}

async function addToCart(productId) {
    const p   = products.find(x => x.id == productId);
    if (!p) return;
    const idx = cart.findIndex(c => c.product_id == productId);
    if (idx >= 0) {
        if (cart[idx].quantity >= p.stock) {
            showToast('Max stock reached', 'warn');
            return;
        }
        cart[idx].quantity++;
    } else {
        cart.push({
            product_id: p.id,
            title: p.title,
            sku: p.sku,
            platform: p.platform,
            format: p.format,
            price: p.price,
            stock: p.stock,
            quantity: 1
        });
    }
    await syncCart();
    renderInvoice();
    renderProducts();
    showToast(`${p.title} added`, 'ok');
}

async function removeFromCart(productId) {
    cart = cart.filter(c => c.product_id != productId);
    await syncCart();
    renderInvoice();
    renderProducts();
}

async function updateQty(productId, delta) {
    const idx = cart.findIndex(c => c.product_id == productId);
    if (idx < 0) return;
    const newQty = cart[idx].quantity + delta;
    if (newQty <= 0) {
        removeFromCart(productId);
        return;
    }
    if (newQty > cart[idx].stock) {
        showToast('Insufficient stock', 'warn');
        return;
    }
    cart[idx].quantity = newQty;
    await syncCart();
    renderInvoice();
}

async function clearCart() {
    if (cart.length === 0) return;
    if (!confirm('Clear all items from invoice?')) return;
    cart = [];
    await syncCart();
    renderInvoice();
    renderProducts();
}

async function syncCart() {
    try {
        await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set', cart }),
        });
    } catch (e) {
        // Not critical: finalize.php re-checks the cart anyway
    }
}

function setClientType(type) {
    clientType = type;
    document.querySelectorAll('.ctype-btn').forEach(b => b.classList.toggle('active', b.dataset.type === type));
    const row = document.getElementById('ewt-row');
    if (type === 'corporate') {
        row.classList.add('show');
    } else {
        row.classList.remove('show');
        setEwtRate(0);
    }
}

function setEwtRate(rate) {
    ewtRate = rate;
    document.querySelectorAll('.ewt-btn')
        .forEach(b => b.classList.toggle('active', parseInt(b.dataset.rate) === rate));
    renderInvoice();
}

function openFinalizeModal() {
    const name = document.getElementById('client-name').value.trim();
    if (!name) {
        showToast('Enter client name first', 'warn');
        document.getElementById('client-name').focus();
        return;
    }
    if (cart.length === 0) {
        showToast('Invoice is empty', 'err');
        return;
    }
    const { subtotal, vatAmount, ewtAmount, total } = calcTotals();
    const invNo = document.getElementById('inv-no-display').textContent;
    document.getElementById('modal-finalize-body').innerHTML = `
        <div style="display:grid;gap:0">
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">Invoice No</span><span style="font-family:'Roboto Mono',monospace;color:var(--accent);font-weight:700">${esc(invNo)}</span></div>
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">Client</span><span style="font-weight:600;color:var(--text)">${esc(name)}</span></div>
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">Type</span><span style="text-transform:capitalize;color:var(--text-2)">${clientType}</span></div>
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">Line Items</span><span style="font-family:'Roboto Mono',monospace;font-weight:600">${cart.length}</span></div>
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">Subtotal</span><span style="font-family:'Roboto Mono',monospace">${fmtPHP(subtotal)}</span></div>
            <div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">VAT (12%)</span><span style="font-family:'Roboto Mono',monospace">${fmtPHP(vatAmount)}</span></div>
            ${ewtRate>0?`<div class="tax-row" style="padding:8px 0;border-bottom:1px solid var(--border)"><span class="tx-lbl">EWT (${ewtRate}%)</span><span style="font-family:'Roboto Mono',monospace;color:var(--heat)">-${fmtPHP(ewtAmount)}</span></div>`:''}
            <div class="total-bar" style="margin-top:10px"><span class="total-lbl">Total Due</span><span class="total-val">${fmtPHP(total)}</span></div>
        </div>`;
    openModal('modal-finalize');
}

async function confirmFinalize() {
    const btn  = document.getElementById('btn-confirm');
    const name = document.getElementById('client-name').value.trim();
    btn.disabled = true;
    btn.textContent = 'Processing...';
    try {
        const res  = await fetch('api/finalize.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ client_name: name, client_type: clientType, ewt_rate: ewtRate, cart }),
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modal-finalize');
            showToast(`${data.invoice_no} created!`, 'ok');
            cart = [];
            document.getElementById('client-name').value = '';
            setClientType('individual');
            await refreshProducts();
            renderInvoice();
            document.getElementById('inv-no-display').textContent = bumpInvoiceNo(data.invoice_no);
            setTimeout(() => window.open(`invoice.php?no=${encodeURIComponent(data.invoice_no)}`, '_blank'), 500);
        } else {
            showToast(data.error || 'Finalize failed', 'err');
        }
    } catch (e) {
        showToast('Network error: ' + e.message, 'err');
    }
    btn.disabled = false;
    btn.textContent = 'Confirm & Generate';
}

async function refreshProducts() {
    try {
        const r = await fetch('api/products.php');
        products = await r.json();
        renderProducts();
    } catch (e) {
        // Ignore; keep the current catalog
    }
}

// Guess the next invoice number (the server assigns the real one)
function bumpInvoiceNo(no) {
    return no.replace(/(\d{4})$/, n => String(parseInt(n) + 1).padStart(4, '0'));
}

function toggleProductsNav() {
    const toggle = document.getElementById('nav-products-toggle');
    const sub    = document.getElementById('nav-products-sub');
    const open   = toggle.classList.toggle('expanded');
    sub.classList.toggle('open', open);
    sub.style.maxHeight = open ? sub.scrollHeight + 'px' : '0';
}

function setFilter(platform) {
    currentFilter = platform;
    document.querySelectorAll('.ftab').forEach(t => t.classList.toggle('active', t.dataset.platform === platform));
    renderProducts();
}

let searchDebounce;
document.getElementById('search-input').addEventListener('input', function() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => { searchQuery = this.value; renderProducts(); }, 140);
});

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => {
    if (e.target === m) closeModal(m.id);
}));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-bg.open').forEach(m => closeModal(m.id));
});

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

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('inv-date-display').textContent = new Date().toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
    document.querySelector('.ewt-btn[data-rate="0"]').classList.add('active');
    renderProducts();
    renderInvoice();
});
</script>

</body>
</html>
