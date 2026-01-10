<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Profit & Loss Statement</h1>
    </div>
    <div class="d-flex gap-2">
         <a href="<?= base_url('reports/profit-loss?start_date=' . ($start_date ?? date('Y-01-01')) . '&end_date=' . ($end_date ?? date('Y-12-31')) . '&format=pdf') ?>" class="btn btn-danger btn-sm">
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
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? date('Y-01-01')) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? date('Y-12-31')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

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

