<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$uri_segments = explode('/', trim($current_url, '/'));
$is_dashboard = (empty($current_url) || $current_url === 'accounting') && !isset($uri_segments[1]);
$is_accounts = isset($uri_segments[0]) && $uri_segments[0] === 'accounts';
$is_cash = isset($uri_segments[0]) && $uri_segments[0] === 'cash';
$is_receivables = isset($uri_segments[0]) && $uri_segments[0] === 'receivables';
$is_payables = isset($uri_segments[0]) && $uri_segments[0] === 'payables';
$is_payroll = isset($uri_segments[0]) && $uri_segments[0] === 'payroll';
$is_employees = isset($uri_segments[0]) && $uri_segments[0] === 'employees';
$is_ledger = isset($uri_segments[0]) && $uri_segments[0] === 'ledger';
?>

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $is_accounts ? 'active' : '' ?>" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link <?= $is_cash ? 'active' : '' ?>" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link <?= $is_receivables ? 'active' : '' ?>" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link <?= $is_payables ? 'active' : '' ?>" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link <?= $is_payroll ? 'active' : '' ?>" href="<?= base_url('payroll') ?>">
            <i class="bi bi-cash-stack"></i> Payroll
        </a>
        <a class="nav-link <?= $is_employees ? 'active' : '' ?>" href="<?= base_url('employees') ?>">
            <i class="bi bi-people"></i> Employees
        </a>
        <a class="nav-link <?= $is_ledger ? 'active' : '' ?>" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

