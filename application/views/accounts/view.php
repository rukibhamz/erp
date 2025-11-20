<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Account: <?= htmlspecialchars($account['account_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('accounts', 'update')): ?>
                <a href="<?= base_url('accounts/edit/' . $account['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('accounts') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Account Code</dt>
                    <dd><?= htmlspecialchars($account['account_code'] ?? 'N/A') ?></dd>
                    
                    <dt>Account Name</dt>
                    <dd><?= htmlspecialchars($account['account_name'] ?? 'N/A') ?></dd>
                    
                    <?php if ($account_number_enabled ?? false): ?>
                    <dt>Account Number</dt>
                    <dd><?= htmlspecialchars($account['account_number'] ?? '-') ?></dd>
                    <?php endif; ?>
                    
                    <dt>Account Type</dt>
                    <dd><span class="badge bg-secondary"><?= htmlspecialchars($account['account_type'] ?? '') ?></span></dd>
                    
                    <dt>Currency</dt>
                    <dd><?= htmlspecialchars($account['currency'] ?? 'USD') ?></dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($account['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($account['status'] ?? 'inactive') ?>
                        </span>
                    </dd>
                    
                    <dt>Opening Balance</dt>
                    <dd><?= format_currency($account['opening_balance'] ?? 0, $account['currency'] ?? 'USD') ?></dd>
                    
                    <dt>Current Balance</dt>
                    <dd class="fs-4 fw-bold"><?= format_currency($account['balance'] ?? 0, $account['currency'] ?? 'USD') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Account Ledger</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($ledger)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ledger as $entry): ?>
                                    <tr>
                                        <td><?= format_date($entry['transaction_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($entry['description'] ?? '') ?></td>
                                        <td class="text-end">
                                            <?php if ($entry['debit'] > 0): ?>
                                                <?= format_currency($entry['debit'], $account['currency'] ?? 'USD') ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($entry['credit'] > 0): ?>
                                                <?= format_currency($entry['credit'], $account['currency'] ?? 'USD') ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold"><?= format_currency($entry['running_balance'] ?? 0, $account['currency'] ?? 'USD') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No transactions found for this account.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

