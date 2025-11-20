<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">General Ledger</h1>
        <a href="<?= base_url('reports') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('reports/general-ledger') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="account_id" class="form-label">Account <span class="text-danger">*</span></label>
                <select class="form-select" id="account_id" name="account_id" required>
                    <option value="">Select Account</option>
                    <?php if (!empty($accounts)): ?>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc['id'] ?>" <?= ($selected_account_id ?? '') == $acc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc['account_code'] . ' - ' . $acc['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>
</div>

<?php if ($account): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5>Account: <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?></h5>
            <p class="mb-0">Type: <span class="badge bg-secondary"><?= htmlspecialchars($account['account_type'] ?? '') ?></span></p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">General Ledger</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($ledger)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
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
                                    <td><?= htmlspecialchars($entry['description'] ?? '-') ?></td>
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
                <p class="text-muted">No transactions found for this account in the selected period.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        Please select an account to view the general ledger.
    </div>
<?php endif; ?>

