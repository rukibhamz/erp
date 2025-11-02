<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$uri_segments = explode('/', trim($current_url, '/'));
$is_dashboard = (empty($current_url) || $current_url === 'inventory') && !isset($uri_segments[1]);
$is_items = isset($uri_segments[1]) && $uri_segments[1] === 'items';
$is_locations = isset($uri_segments[1]) && $uri_segments[1] === 'locations';
$is_movements = isset($uri_segments[1]) && ($uri_segments[1] === 'receive' || $uri_segments[1] === 'issue' || $uri_segments[1] === 'transfer');
$is_adjustments = isset($uri_segments[1]) && $uri_segments[1] === 'adjustments';
$is_stock_takes = isset($uri_segments[1]) && $uri_segments[1] === 'stock-takes';
$is_purchasing = isset($uri_segments[1]) && ($uri_segments[1] === 'purchase-orders' || $uri_segments[1] === 'goods-receipts');
$is_suppliers = isset($uri_segments[1]) && $uri_segments[1] === 'suppliers';
$is_reports = isset($uri_segments[1]) && $uri_segments[1] === 'reports';
$is_assets = isset($uri_segments[1]) && $uri_segments[1] === 'assets';
?>

<!-- Inventory Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('inventory') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $is_items ? 'active' : '' ?>" href="<?= base_url('inventory/items') ?>">
            <i class="bi bi-box"></i> Items
        </a>
        <a class="nav-link <?= $is_locations ? 'active' : '' ?>" href="<?= base_url('inventory/locations') ?>">
            <i class="bi bi-geo-alt"></i> Locations
        </a>
        <a class="nav-link <?= $is_movements ? 'active' : '' ?>" href="<?= base_url('inventory/receive') ?>">
            <i class="bi bi-arrow-left-right"></i> Movements
        </a>
        <a class="nav-link <?= $is_adjustments ? 'active' : '' ?>" href="<?= base_url('inventory/adjustments') ?>">
            <i class="bi bi-pencil-square"></i> Adjustments
        </a>
        <a class="nav-link <?= $is_stock_takes ? 'active' : '' ?>" href="<?= base_url('inventory/stock-takes') ?>">
            <i class="bi bi-clipboard-check"></i> Stock Takes
        </a>
        <a class="nav-link <?= $is_purchasing ? 'active' : '' ?>" href="<?= base_url('inventory/purchase-orders') ?>">
            <i class="bi bi-cart-plus"></i> Purchasing
        </a>
        <a class="nav-link <?= $is_suppliers ? 'active' : '' ?>" href="<?= base_url('inventory/suppliers') ?>">
            <i class="bi bi-truck"></i> Suppliers
        </a>
        <a class="nav-link <?= $is_assets ? 'active' : '' ?>" href="<?= base_url('inventory/assets') ?>">
            <i class="bi bi-building"></i> Assets
        </a>
        <a class="nav-link <?= $is_reports ? 'active' : '' ?>" href="<?= base_url('inventory/reports') ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>
    </nav>
</div>

<style>
.property-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.property-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.property-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.property-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.property-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

