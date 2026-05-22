<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Trial Balance</h1>
    </div>
    <div class="d-flex gap-2">
         <a href="<?= base_url('reports/trial-balance?as_of_date=' . urlencode($as_of_date ?? date('Y-m-d')) . '&format=pdf') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf"></i> Export PDF
        </a>
        <a href="<?= base_url('reports') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="as_of_date" class="form-label">As Of Date</label>
                <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="<?= htmlspecialchars($as_of_date ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Trial Balance Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Trial Balance Report</h5>
        <small class="text-muted">As of: <?= format_date($as_of_date ?? '') ?></small>
    </div>
    <div class="card-body">
        <?php if (!empty($trial_balance)): ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotalDebit = 0;
                        $grandTotalCredit = 0;
                        foreach ($trial_balance as $row): 
                            $grandTotalDebit += floatval($row['total_debit'] ?? 0);
                            $grandTotalCredit += floatval($row['total_credit'] ?? 0);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['account_code'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['account_name'] ?? '') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['account_type'] ?? '') ?></span></td>
                                <td class="text-end"><?= format_currency($row['total_debit'] ?? 0) ?></td>
                                <td class="text-end"><?= format_currency($row['total_credit'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary fw-bold">
                            <td colspan="3" class="text-end">Grand Totals:</td>
                            <td class="text-end"><?= format_currency($grandTotalDebit) ?></td>
                            <td class="text-end"><?= format_currency($grandTotalCredit) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if (abs($grandTotalDebit - $grandTotalCredit) < 0.01): ?>
                <div class="alert alert-success mt-3">
                    <i class="bi bi-check-circle"></i> Trial balance is balanced. Debits equal credits.
                </div>
            <?php else: ?>
                <div class="alert alert-danger mt-3">
                    <i class="bi bi-exclamation-triangle"></i> Trial balance is not balanced! 
                    Difference: <?= format_currency(abs($grandTotalDebit - $grandTotalCredit)) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted">No data found for the selected period.</p>
        <?php endif; ?>
    </div>
</div>

