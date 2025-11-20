<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Accounting Dashboard</h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Cash Balance</h6>
                        <h3 class="card-title mb-0 fw-bold"><?= format_large_currency($cash_balance ?? 0, 'NGN', 1) ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-wallet2" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Receivables</h6>
                        <h3 class="card-title mb-0 fw-bold text-info"><?= format_large_currency($receivables ?? 0, 'NGN', 1) ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-arrow-down-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Payables</h6>
                        <h3 class="card-title mb-0 fw-bold text-danger"><?= format_large_currency($payables ?? 0, 'NGN', 1) ?></h3>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-arrow-up-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Profit/Loss (This Month)</h6>
                        <h3 class="card-title mb-0 fw-bold <?= ($profit_loss ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= format_large_currency($profit_loss ?? 0, 'NGN', 1) ?>
                        </h3>
                    </div>
                    <div class="<?= ($profit_loss ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="bi <?= ($profit_loss ?? 0) >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' ?>" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_transactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $txn): ?>
                                    <tr>
                                        <td><?= format_date($txn['transaction_date']) ?></td>
                                        <td><?= htmlspecialchars($txn['account_code'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($txn['description'] ?? '') ?></td>
                                        <td class="text-end">
                                            <?php if ($txn['debit'] > 0): ?>
                                                <span class="text-danger"><?= format_currency($txn['debit']) ?></span>
                                            <?php else: ?>
                                                <span class="text-success"><?= format_currency($txn['credit']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent transactions</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Overdue Invoices</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($overdue_invoices)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Due Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_invoices as $invoice): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('receivables/invoices/view/' . $invoice['id']) ?>">
                                                <?= htmlspecialchars($invoice['invoice_number'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($invoice['company_name'] ?? '') ?></td>
                                        <td><?= format_date($invoice['due_date']) ?></td>
                                        <td class="text-end"><?= format_currency($invoice['balance_amount'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No overdue invoices</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

