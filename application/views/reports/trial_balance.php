<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Trial Balance</h1>
        <a href="<?= base_url('reports') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('reports/trial-balance') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 mb-2">Generate Report</button>
                 <a href="<?= base_url('reports/trial-balance?start_date=' . ($start_date ?? date('Y-m-01')) . '&end_date=' . ($end_date ?? date('Y-m-t')) . '&format=pdf') ?>" class="btn btn-outline-danger w-100">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Trial Balance Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Trial Balance Report</h5>
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

