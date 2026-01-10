<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Statement of Cash Flows</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('reports/cash-flow?start_date=' . ($start_date ?? date('Y-01-01')) . '&end_date=' . ($end_date ?? date('Y-12-31')) . '&format=pdf') ?>" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf"></i> Export PDF
        </a>
        <a href="<?= base_url('reports') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

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

<!-- Cash Flow Statement -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Cash Flow Statement</h5>
        <small class="text-muted">Period: <?= format_date($start_date ?? '') ?> to <?= format_date($end_date ?? '') ?></small>
    </div>
    <div class="card-body">
        <h6 class="fw-bold">Operating Activities</h6>
        <?php if (!empty($operating)): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operating as $txn): ?>
                            <tr>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($txn['debit'] > 0): ?>
                                        <span class="text-danger">-<?= format_currency($txn['debit']) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">+<?= format_currency($txn['credit']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No operating activities</p>
        <?php endif; ?>
        
        <h6 class="fw-bold mt-4">Investing Activities</h6>
        <?php if (!empty($investing)): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($investing as $txn): ?>
                            <tr>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($txn['debit'] > 0): ?>
                                        <span class="text-danger">-<?= format_currency($txn['debit']) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">+<?= format_currency($txn['credit']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No investing activities</p>
        <?php endif; ?>
        
        <h6 class="fw-bold mt-4">Financing Activities</h6>
        <?php if (!empty($financing)): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financing as $txn): ?>
                            <tr>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($txn['debit'] > 0): ?>
                                        <span class="text-danger">-<?= format_currency($txn['debit']) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">+<?= format_currency($txn['credit']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No financing activities</p>
        <?php endif; ?>
    </div>
</div>

