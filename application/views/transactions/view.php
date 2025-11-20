<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Transaction: <?= htmlspecialchars($transaction['transaction_number'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('accounting', 'update') && ($transaction['status'] ?? '') !== 'posted'): ?>
                <a href="<?= base_url('transactions/edit/' . $transaction['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('transactions') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Transaction Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Transaction Number</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($transaction['transaction_number'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Transaction Date</dt>
                    <dd class="col-sm-8"><?= format_date($transaction['transaction_date'] ?? '') ?></dd>
                    
                    <dt class="col-sm-4">Account</dt>
                    <dd class="col-sm-8">
                        <?php if ($account): ?>
                            <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($transaction['description'] ?? '-') ?></dd>
                    
                    <dt class="col-sm-4">Transaction Type</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars(ucfirst($transaction['transaction_type'] ?? 'manual')) ?></dd>
                    
                    <dt class="col-sm-4">Debit Amount</dt>
                    <dd class="col-sm-8">
                        <?php if ($transaction['debit'] > 0): ?>
                            <span class="text-danger fw-bold"><?= format_currency($transaction['debit']) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Credit Amount</dt>
                    <dd class="col-sm-8">
                        <?php if ($transaction['credit'] > 0): ?>
                            <span class="text-success fw-bold"><?= format_currency($transaction['credit']) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                    
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= ($transaction['status'] ?? '') === 'posted' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($transaction['status'] ?? 'draft') ?>
                        </span>
                    </dd>
                    
                    <?php if (!empty($transaction['reference'])): ?>
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($transaction['reference']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($transaction['reference_type'])): ?>
                        <dt class="col-sm-4">Reference Type</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($transaction['reference_type']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

