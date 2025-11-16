<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$uri_segments = explode('/', trim($current_url, '/'));
$is_dashboard = (empty($current_url) || $current_url === 'utilities') && !isset($uri_segments[1]);
$is_meters = isset($uri_segments[1]) && $uri_segments[1] === 'meters';
$is_readings = isset($uri_segments[1]) && $uri_segments[1] === 'readings';
$is_bills = isset($uri_segments[1]) && $uri_segments[1] === 'bills';
$is_providers = isset($uri_segments[1]) && $uri_segments[1] === 'providers';
$is_tariffs = isset($uri_segments[1]) && $uri_segments[1] === 'tariffs';
$is_payments = isset($uri_segments[1]) && $uri_segments[1] === 'payments';
$is_reports = isset($uri_segments[1]) && $uri_segments[1] === 'reports';
$is_alerts = isset($uri_segments[1]) && $uri_segments[1] === 'alerts';
?>

<!-- Utilities Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('utilities') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $is_meters ? 'active' : '' ?>" href="<?= base_url('utilities/meters') ?>">
            <i class="bi bi-speedometer"></i> Meters
        </a>
        <a class="nav-link <?= $is_readings ? 'active' : '' ?>" href="<?= base_url('utilities/readings') ?>">
            <i class="bi bi-clipboard-data"></i> Readings
        </a>
        <a class="nav-link <?= $is_bills ? 'active' : '' ?>" href="<?= base_url('utilities/bills') ?>">
            <i class="bi bi-receipt"></i> Bills
        </a>
        <a class="nav-link <?= $is_providers ? 'active' : '' ?>" href="<?= base_url('utilities/providers') ?>">
            <i class="bi bi-building"></i> Providers
        </a>
        <a class="nav-link <?= $is_tariffs ? 'active' : '' ?>" href="<?= base_url('utilities/tariffs') ?>">
            <i class="bi bi-currency-exchange"></i> Tariffs
        </a>
        <a class="nav-link <?= $is_payments ? 'active' : '' ?>" href="<?= base_url('utilities/payments') ?>">
            <i class="bi bi-cash"></i> Payments
        </a>
        <a class="nav-link <?= $is_reports ? 'active' : '' ?>" href="<?= base_url('utilities/reports') ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>
        <a class="nav-link <?= (isset($uri_segments[1]) && $uri_segments[1] === 'vendor-bills') ? 'active' : '' ?>" href="<?= base_url('utilities/vendor-bills') ?>">
            <i class="bi bi-file-earmark-text"></i> Vendor Bills
        </a>
        <a class="nav-link <?= $is_alerts ? 'active' : '' ?>" href="<?= base_url('utilities/alerts') ?>">
            <i class="bi bi-bell"></i> Alerts
        </a>
    </nav>
</div>


