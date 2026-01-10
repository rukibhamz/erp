<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">General Ledger</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('reports/general-ledger?account_id=' . ($selected_account_id ?? '') . '&format=pdf&start_date=' . ($start_date ?? '') . '&end_date=' . ($end_date ?? '')) ?>" class="btn btn-danger btn-sm" title="Export PDF">
            <i class="bi bi-file-pdf"></i> Export PDF
        </a>
        <a href="<?= base_url('reports/general-ledger?account_id=' . ($selected_account_id ?? '') . '&format=excel&start_date=' . ($start_date ?? '') . '&end_date=' . ($end_date ?? '')) ?>" class="btn btn-success btn-sm" title="Export Excel">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </a>
        <a href="<?= base_url('reports') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="account_id" class="form-label">Account</label>
                <select name="account_id" id="account_id" class="form-select select2">
                    <option value="">Select Account</option>
                    <?php foreach ($accounts ?? [] as $account): ?>
                        <option value="<?= $account['id'] ?>" <?= ($selected_account_id == $account['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']) ?>
                        </option>
                    <?php endforeach; ?>
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
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- General Ledger -->
<?php if ($selected_account): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <?= htmlspecialchars($selected_account['account_code'] . ' - ' . $selected_account['account_name']) ?>
            </h5>
            <small class="text-muted">
                Period: <?= format_date($start_date ?? '') ?> to <?= format_date($end_date ?? '') ?>
            </small>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="alert alert-light border">
                        <small class="text-muted">Opening Balance</small>
                        <h4 class="mb-0"><?= format_currency($opening_balance ?? 0) ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-light border">
                        <small class="text-muted">Total Debit</small>
                        <h4 class="mb-0 text-danger">
                            <?php 
                            $totalDebit = 0;
                            foreach ($transactions ?? [] as $txn) { $totalDebit += $txn['debit']; }
                            echo format_currency($totalDebit);
                            ?>
                        </h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-light border">
                        <small class="text-muted">Total Credit</small>
                        <h4 class="mb-0 text-success">
                            <?php 
                            $totalCredit = 0;
                            foreach ($transactions ?? [] as $txn) { $totalCredit += $txn['credit']; }
                            echo format_currency($totalCredit);
                            ?>
                        </h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Date</th>
                            <th>Description</th>
                            <th style="width: 140px;" class="text-end">Debit</th>
                            <th style="width: 140px;" class="text-end">Credit</th>
                            <th style="width: 140px;" class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $runningBalance = $opening_balance ?? 0;
                        ?>
                        <tr class="table-light">
                            <td colspan="4"><strong>Opening Balance</strong></td>
                            <td class="text-end"><strong><?= format_currency($runningBalance) ?></strong></td>
                        </tr>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $txn): 
                                $runningBalance += floatval($txn['debit']) - floatval($txn['credit']);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($txn['entry_date']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($txn['description']) ?>
                                        <?php if (!empty($txn['line_description'])): ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($txn['line_description']) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($txn['reference_type'])): ?>
                                            <small class="text-muted">
                                                Ref: <?= htmlspecialchars($txn['reference_type']) ?>
                                                <?php if (!empty($txn['reference_id'])): ?>
                                                    #<?= intval($txn['reference_id']) ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= floatval($txn['debit']) > 0 ? format_currency($txn['debit']) : '-' ?></td>
                                    <td class="text-end"><?= floatval($txn['credit']) > 0 ? format_currency($txn['credit']) : '-' ?></td>
                                    <td class="text-end"><?= format_currency($runningBalance) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No transactions found for this account in the selected period.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="4">Closing Balance</td>
                            <td class="text-end"><?= format_currency($runningBalance) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-book display-1 text-muted"></i>
            <h5 class="mt-3">Select an Account</h5>
            <p class="text-muted">Choose an account from the dropdown above to view its ledger transactions.</p>
        </div>
    </div>
<?php endif; ?>
