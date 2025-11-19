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
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Cash Balance</h6>
                <h3 class="card-title"><?= format_currency($cash_balance ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Receivables</h6>
                <h3 class="card-title text-info"><?= format_currency($receivables ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Payables</h6>
                <h3 class="card-title text-danger"><?= format_currency($payables ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Profit/Loss (This Month)</h6>
                <h3 class="card-title <?= ($profit_loss ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= format_currency($profit_loss ?? 0) ?>
                </h3>
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

