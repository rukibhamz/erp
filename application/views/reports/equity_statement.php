<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Statement of Changes in Equity</h1>
        <p class="text-muted">For the period <?= format_date($start_date) ?> to <?= format_date($end_date) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&format=pdf" class="btn btn-outline-danger btn-sm">
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
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Beginning Balance (as of <?= format_date($start_date) ?>)</strong></td>
                        <td class="text-end"><strong>₦<?= number_format($opening_balance, 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="ps-4">Net Income for the Period</td>
                        <td class="text-end">₦<?= number_format($net_income, 2) ?></td>
                    </tr>
                    
                    <?php if (!empty($adjustments)): ?>
                        <tr>
                            <td colspan="2" class="bg-light"><strong>Other Equity Adjustments</strong></td>
                        </tr>
                        <?php foreach ($adjustments as $adj): ?>
                            <tr>
                                <td class="ps-4">
                                    <?= htmlspecialchars($adj['description']) ?>
                                    <small class="text-muted d-block"><?= format_date($adj['date']) ?></small>
                                </td>
                                <td class="text-end <?= $adj['amount'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    ₦<?= number_format($adj['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="ps-4"><strong>Total Other Adjustments</strong></td>
                            <td class="text-end"><strong>₦<?= number_format($total_adjustments, 2) ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td><h5 class="mb-0">Ending Balance (as of <?= format_date($end_date) ?>)</h5></td>
                        <td class="text-end"><h5 class="mb-0">₦<?= number_format($closing_balance, 2) ?></h5></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
