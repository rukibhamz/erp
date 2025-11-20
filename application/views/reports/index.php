<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Financial Reports</h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-list-check"></i> Trial Balance
                </h5>
                <p class="card-text">View all account balances and verify that debits equal credits.</p>
                <a href="<?= base_url('reports/trial-balance') ?>" class="btn btn-primary">
                    View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-journal-text"></i> General Ledger
                </h5>
                <p class="card-text">View detailed transaction history for a specific account.</p>
                <a href="<?= base_url('reports/general-ledger') ?>" class="btn btn-primary">
                    View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-graph-up-arrow"></i> Profit & Loss Statement
                </h5>
                <p class="card-text">View revenue, expenses, and net income for a period.</p>
                <a href="<?= base_url('reports/profit-loss') ?>" class="btn btn-primary">
                    View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-pie-chart"></i> Balance Sheet
                </h5>
                <p class="card-text">View assets, liabilities, and equity at a specific date.</p>
                <a href="<?= base_url('reports/balance-sheet') ?>" class="btn btn-primary">
                    View Report
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-cash-coin"></i> Cash Flow Statement
                </h5>
                <p class="card-text">View cash inflows and outflows for a period.</p>
                <a href="<?= base_url('reports/cash-flow') ?>" class="btn btn-primary">
                    View Report
                </a>
            </div>
        </div>
    </div>
</div>

