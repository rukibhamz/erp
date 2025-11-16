<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Accounting Dashboard</h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Financial KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon success me-3">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_currency($cash_balance ?? 0) ?></div>
                    <div class="stat-label">Cash Balance</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon info me-3">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_currency($receivables ?? 0) ?></div>
                    <div class="stat-label">Accounts Receivable</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon warning me-3">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_currency($payables ?? 0) ?></div>
                    <div class="stat-label">Accounts Payable</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon <?= ($profit_loss ?? 0) >= 0 ? 'success' : 'danger' ?> me-3">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_currency($profit_loss ?? 0) ?></div>
                    <div class="stat-label">Profit/Loss (This Month)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= base_url('accounts/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Account
                    </a>
                    <a href="<?= base_url('journal/create') ?>" class="btn btn-primary">
                        <i class="bi bi-journal-text"></i> Journal Entry
                    </a>
                    <a href="<?= base_url('receivables/invoices/create') ?>" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text"></i> New Invoice
                    </a>
                    <a href="<?= base_url('payables/bills/create') ?>" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> New Bill
                    </a>
                    <a href="<?= base_url('cash/receipts/create') ?>" class="btn btn-primary">
                        <i class="bi bi-cash-coin"></i> Cash Receipt
                    </a>
                    <a href="<?= base_url('cash/payments/create') ?>" class="btn btn-primary">
                        <i class="bi bi-credit-card"></i> Cash Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions and Overdue Items -->
<div class="row g-3">
    <!-- Recent Transactions -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_transactions)): ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($transaction['transaction_date'])) ?></td>
                                        <td>
                                            <span class="text-muted"><?= htmlspecialchars($transaction['account_code']) ?></span>
                                            <br><small><?= htmlspecialchars($transaction['account_name']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($transaction['description'] ?? 'N/A') ?></td>
                                        <td><?= $transaction['debit'] > 0 ? format_currency($transaction['debit']) : '-' ?></td>
                                        <td><?= $transaction['credit'] > 0 ? format_currency($transaction['credit']) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No recent transactions</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Overdue Items -->
    <div class="col-lg-4">
        <!-- Overdue Invoices -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Overdue Invoices</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($overdue_invoices)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($overdue_invoices as $invoice): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($invoice['company_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($invoice['invoice_number']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-danger"><?= format_currency($invoice['balance_amount']) ?></strong>
                                        <br><small class="text-muted"><?= date('M d', strtotime($invoice['due_date'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= base_url('receivables/invoices?status=overdue') ?>" class="btn btn-sm btn-outline-danger mt-2 w-100">View All</a>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No overdue invoices</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Overdue Bills -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Overdue Bills</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($overdue_bills)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($overdue_bills as $bill): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($bill['company_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($bill['bill_number']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-danger"><?= format_currency($bill['balance_amount']) ?></strong>
                                        <br><small class="text-muted"><?= date('M d', strtotime($bill['due_date'])) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= base_url('payables/bills?status=overdue') ?>" class="btn btn-sm btn-outline-danger mt-2 w-100">View All</a>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No overdue bills</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


