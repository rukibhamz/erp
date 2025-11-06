<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Cash Management</h1>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-wallet2 fs-1 text-primary mb-3"></i>
                <h5>Cash Accounts</h5>
                <p class="text-muted">Manage bank accounts and petty cash</p>
                <a href="<?= base_url('cash/accounts') ?>" class="btn btn-primary">View Accounts</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-arrow-down-circle fs-1 text-success mb-3"></i>
                <h5>Cash Receipts</h5>
                <p class="text-muted">Record money received</p>
                <a href="<?= base_url('cash/receipts') ?>" class="btn btn-success">Record Receipt</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-arrow-up-circle fs-1 text-danger mb-3"></i>
                <h5>Cash Payments</h5>
                <p class="text-muted">Record money paid out</p>
                <a href="<?= base_url('cash/payments') ?>" class="btn btn-danger">Record Payment</a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($cash_accounts)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Cash Accounts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th>Type</th>
                        <th>Bank</th>
                        <th>Account Number</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cash_accounts as $account): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($account['account_name']) ?></strong></td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'petty_cash' => 'Petty Cash',
                                    'bank_account' => 'Bank Account',
                                    'cash_register' => 'Cash Register'
                                ];
                                echo $typeLabels[$account['account_type']] ?? $account['account_type'];
                                ?>
                            </td>
                            <td><?= htmlspecialchars($account['bank_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($account['account_number'] ?? '-') ?></td>
                            <td class="text-end"><strong><?= format_currency($account['current_balance']) ?></strong></td>
                            <td>
                                <span class="badge bg-<?= $account['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($account['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-wallet2 fs-1 text-muted mb-3"></i>
        <h5>No Cash Accounts</h5>
        <p class="text-muted">Create your first cash account to get started</p>
        <a href="<?= base_url('cash/accounts/create') ?>" class="btn btn-primary">Create Account</a>
    </div>
</div>
<?php endif; ?>

