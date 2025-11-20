<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Get current URL to determine active tab
$current_url = $_GET['url'] ?? '';
$current_url = trim($current_url, '/');
$uri_segments = !empty($current_url) ? explode('/', $current_url) : [];

// Determine active sections
$is_dashboard = (empty($current_url) || $current_url === 'accounting') && !isset($uri_segments[1]);
$is_accounts = isset($uri_segments[0]) && $uri_segments[0] === 'accounts';
$is_transactions = isset($uri_segments[0]) && $uri_segments[0] === 'transactions';
$is_ledger = isset($uri_segments[0]) && $uri_segments[0] === 'ledger';
$is_reports = isset($uri_segments[0]) && $uri_segments[0] === 'reports';
$is_cash = isset($uri_segments[0]) && $uri_segments[0] === 'cash';
$is_receivables = isset($uri_segments[0]) && $uri_segments[0] === 'receivables';
$is_payables = isset($uri_segments[0]) && $uri_segments[0] === 'payables';
?>

<!-- Accounting Module Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= $is_dashboard ? 'active' : '' ?>" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $is_accounts ? 'active' : '' ?>" href="<?= base_url('accounts') ?>">
            <i class="bi bi-list-ul"></i> Chart of Accounts
        </a>
        <a class="nav-link <?= $is_transactions ? 'active' : '' ?>" href="<?= base_url('transactions') ?>">
            <i class="bi bi-arrow-left-right"></i> Transactions
        </a>
        <a class="nav-link <?= $is_ledger ? 'active' : '' ?>" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> Journal Entries
        </a>
        <a class="nav-link <?= $is_reports ? 'active' : '' ?>" href="<?= base_url('reports') ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>
        <a class="nav-link <?= $is_cash ? 'active' : '' ?>" href="<?= base_url('cash/accounts') ?>">
            <i class="bi bi-wallet2"></i> Cash Accounts
        </a>
        <a class="nav-link <?= $is_receivables ? 'active' : '' ?>" href="<?= base_url('receivables') ?>">
            <i class="bi bi-people"></i> Receivables
        </a>
        <a class="nav-link <?= $is_payables ? 'active' : '' ?>" href="<?= base_url('payables') ?>">
            <i class="bi bi-building"></i> Payables
        </a>
    </nav>
</div>

