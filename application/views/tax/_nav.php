<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$uri_segments = explode('/', trim($current_url, '/'));
$is_dashboard = (empty($current_url) || $current_url === 'tax') && !isset($uri_segments[1]);
$is_vat = isset($uri_segments[1]) && $uri_segments[1] === 'vat';
$is_wht = isset($uri_segments[1]) && $uri_segments[1] === 'wht';
$is_cit = isset($uri_segments[1]) && $uri_segments[1] === 'cit';
$is_paye = isset($uri_segments[1]) && $uri_segments[1] === 'paye';
$is_payments = isset($uri_segments[1]) && $uri_segments[1] === 'payments';
$is_compliance = isset($uri_segments[1]) && $uri_segments[1] === 'compliance';
$is_reports = isset($uri_segments[1]) && $uri_segments[1] === 'reports';
$is_config = isset($uri_segments[1]) && $uri_segments[1] === 'config';
$is_settings = isset($uri_segments[1]) && $uri_segments[1] === 'settings';
?>

<!-- Tax Module Navigation -->
<div class="tax-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('tax') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $is_vat ? 'active' : '' ?>" href="<?= base_url('tax/vat') ?>">
            <i class="bi bi-receipt-cutoff"></i> VAT
        </a>
        <a class="nav-link <?= $is_wht ? 'active' : '' ?>" href="<?= base_url('tax/wht') ?>">
            <i class="bi bi-cash-stack"></i> WHT
        </a>
        <a class="nav-link <?= $is_cit ? 'active' : '' ?>" href="<?= base_url('tax/cit') ?>">
            <i class="bi bi-building"></i> CIT
        </a>
        <a class="nav-link <?= $is_paye ? 'active' : '' ?>" href="<?= base_url('tax/paye') ?>">
            <i class="bi bi-people"></i> PAYE
        </a>
        <a class="nav-link <?= $is_payments ? 'active' : '' ?>" href="<?= base_url('tax/payments') ?>">
            <i class="bi bi-cash-coin"></i> Payments
        </a>
        <a class="nav-link <?= $is_compliance ? 'active' : '' ?>" href="<?= base_url('tax/compliance') ?>">
            <i class="bi bi-calendar-event"></i> Compliance
        </a>
        <a class="nav-link <?= $is_reports ? 'active' : '' ?>" href="<?= base_url('tax/reports') ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>
        <a class="nav-link <?= $is_config ? 'active' : '' ?>" href="<?= base_url('tax/config') ?>">
            <i class="bi bi-sliders"></i> Configuration
        </a>
        <a class="nav-link <?= $is_settings ? 'active' : '' ?>" href="<?= base_url('tax/settings') ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
    </nav>
</div>


