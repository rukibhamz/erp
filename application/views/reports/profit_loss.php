<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Profit & Loss Statement</h1>
        <a href="<?= base_url('reports') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('reports/profit-loss') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? date('Y-01-01')) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? date('Y-12-31')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 mb-2">Generate Report</button>
                 <a href="<?= base_url('reports/profit-loss?start_date=' . ($start_date ?? date('Y-01-01')) . '&end_date=' . ($end_date ?? date('Y-12-31')) . '&format=pdf') ?>" class="btn btn-outline-danger w-100">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Profit & Loss Statement -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Profit & Loss Statement</h5>
        <small class="text-muted">Period: <?= format_date($start_date ?? '') ?> to <?= format_date($end_date ?? '') ?></small>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Revenue</h6>
                <?php if (!empty($revenue)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($revenue as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) ?></td>
                                    <td class="text-end"><?= format_currency($item['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Revenue</td>
                                <td class="text-end"><?= format_currency($total_revenue ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No revenue transactions</p>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <h6 class="fw-bold">Expenses</h6>
                <?php if (!empty($expenses)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <?php foreach ($expenses as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) ?></td>
                                    <td class="text-end"><?= format_currency($item['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total Expenses</td>
                                <td class="text-end"><?= format_currency($total_expenses ?? 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No expense transactions</p>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?= ($net_income ?? 0) >= 0 ? 'success' : 'danger' ?>">
                    <h5 class="mb-0">
                        Net Income: <?= format_currency($net_income ?? 0) ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

