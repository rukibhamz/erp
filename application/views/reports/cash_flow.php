<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Statement of Cash Flows</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('reports/cash-flow?start_date=' . urlencode($start_date ?? date('Y-m-01')) . '&end_date=' . urlencode($end_date ?? date('Y-m-t')) . '&format=pdf') ?>" class="btn btn-danger btn-sm">
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
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? date('Y-m-t')) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($cash_accounts)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        No cash/bank GL accounts found (chart codes 1000–1099). Add accounts such as <strong>1000 Cash on Hand</strong> and <strong>1010 Cash in Bank</strong>.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-secondary h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Beginning Cash</div>
                <div class="h5 mb-0"><?= format_currency($beginning_cash ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Net Income (period)</div>
                <div class="h5 mb-0"><?= format_currency($net_income ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Net Change in Cash</div>
                <div class="h5 mb-0"><?= format_currency($net_cash_flow ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Ending Cash</div>
                <div class="h5 mb-0"><?= format_currency($ending_cash ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">Cash Flow Statement</h5>
        <small class="text-muted">Period: <?= format_date($start_date ?? '') ?> to <?= format_date($end_date ?? '') ?></small>
        <?php if (!empty($cash_accounts)): ?>
            <div class="small text-muted mt-1">
                Cash accounts:
                <?= htmlspecialchars(implode(', ', array_map(function ($a) {
                    return ($a['account_code'] ?? '') . ' ' . ($a['account_name'] ?? '');
                }, $cash_accounts))) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php
        $sections = [
            'Operating Activities' => [
                'rows' => $operating ?? [],
                'total' => $total_operating ?? 0,
                'show_net_income' => true,
            ],
            'Investing Activities' => [
                'rows' => $investing ?? [],
                'total' => $total_investing ?? 0,
                'show_net_income' => false,
            ],
            'Financing Activities' => [
                'rows' => $financing ?? [],
                'total' => $total_financing ?? 0,
                'show_net_income' => false,
            ],
        ];
        ?>

        <?php foreach ($sections as $title => $section): ?>
            <h6 class="fw-bold <?= $title !== 'Operating Activities' ? 'mt-4' : '' ?>"><?= htmlspecialchars($title) ?></h6>
            <?php if (!empty($section['show_net_income'])): ?>
                <div class="d-flex justify-content-between border-bottom py-2 mb-2">
                    <span>Net income</span>
                    <span class="fw-semibold"><?= format_currency($net_income ?? 0) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($section['rows'])): ?>
                <div class="table-responsive mb-2">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="text-end">Cash movement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['rows'] as $txn): ?>
                                <tr>
                                    <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                    <td class="text-end">
                                        <?php if (($txn['debit'] ?? 0) > 0): ?>
                                            <span class="text-danger">−<?= format_currency($txn['debit']) ?></span>
                                        <?php elseif (($txn['credit'] ?? 0) > 0): ?>
                                            <span class="text-success">+<?= format_currency($txn['credit']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?= format_currency($txn['amount'] ?? 0) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-2">No <?= strtolower(htmlspecialchars($title)) ?> in this period.</p>
            <?php endif; ?>

            <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
                <span>Net cash from <?= strtolower(htmlspecialchars($title)) ?></span>
                <span class="<?= ($section['total'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= format_currency($section['total'] ?? 0) ?>
                </span>
            </div>
        <?php endforeach; ?>

        <div class="table-responsive mt-4">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td>Cash at beginning of period</td>
                        <td class="text-end fw-semibold"><?= format_currency($beginning_cash ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td>Net change in cash</td>
                        <td class="text-end fw-semibold"><?= format_currency($net_cash_flow ?? 0) ?></td>
                    </tr>
                    <tr class="table-success">
                        <td><strong>Cash at end of period</strong></td>
                        <td class="text-end"><strong><?= format_currency($ending_cash ?? 0) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
