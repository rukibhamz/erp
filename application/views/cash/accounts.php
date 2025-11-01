<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Cash Accounts</h1>
        <a href="<?= base_url('cash/accounts/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Account
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th>Type</th>
                        <th>Bank</th>
                        <th>Account Number</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Current Balance</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cash_accounts)): ?>
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
                                <td class="text-end"><?= format_currency($account['opening_balance']) ?></td>
                                <td class="text-end"><strong><?= format_currency($account['current_balance']) ?></strong></td>
                                <td><?= htmlspecialchars($account['currency']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $account['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($account['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('cash/receipts?account=' . $account['id']) ?>" class="btn btn-outline-success" title="Record Receipt">
                                            <i class="bi bi-arrow-down-circle"></i>
                                        </a>
                                        <a href="<?= base_url('cash/payments?account=' . $account['id']) ?>" class="btn btn-outline-danger" title="Record Payment">
                                            <i class="bi bi-arrow-up-circle"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No cash accounts found. <a href="<?= base_url('cash/accounts/create') ?>">Create your first account</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

