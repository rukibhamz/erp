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
                <div class="small text-muted">Net Income (accrual)</div>
                <div class="h5 mb-0"><?= format_currency($net_income ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Cash from Bookings</div>
                <div class="h5 mb-0"><?= format_currency($total_booking_cash ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info h-100">
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
        <h6 class="fw-bold">Operating Activities</h6>
        <p class="small text-muted">
            Net income is from revenue/expense accounts (accrual). <strong>Cash from bookings</strong> shows actual customer payments recorded for venue bookings.
        </p>

        <div class="d-flex justify-content-between border-bottom py-2 mb-3">
            <span>Net income (profit &amp; loss)</span>
            <span class="fw-semibold"><?= format_currency($net_income ?? 0) ?></span>
        </div>

        <h6 class="fw-semibold text-success mb-2"><i class="bi bi-calendar-check"></i> Cash received from bookings</h6>
        <?php if (!empty($booking_receipts)): ?>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Booking</th>
                            <th>Details</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($booking_receipts as $txn): ?>
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
                                        <?= format_currency($txn['amount'] ?? 0) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end">Total cash from bookings</td>
                            <td class="text-end text-success"><?= format_currency($total_booking_cash ?? 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3">No booking payments recorded in this period.</p>
        <?php endif; ?>

        <?php if (!empty($other_operating)): ?>
            <h6 class="fw-semibold mb-2">Other operating cash movements</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($other_operating as $txn): ?>
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
                                        <?= format_currency($txn['amount'] ?? 0) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-4">
            <span>Net cash from operating activities</span>
            <span class="<?= ($total_operating ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= format_currency($total_operating ?? 0) ?>
            </span>
        </div>

        <?php if (!empty($investing) && abs($total_investing ?? 0) >= 0.01): ?>
            <h6 class="fw-bold mt-4">Investing Activities</h6>
            <div class="table-responsive mb-2">
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($investing as $txn): ?>
                            <tr>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                <td class="text-end"><?= format_currency($txn['amount'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
                <span>Net cash from investing</span>
                <span><?= format_currency($total_investing ?? 0) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($financing) && abs($total_financing ?? 0) >= 0.01): ?>
            <h6 class="fw-bold mt-4">Financing Activities</h6>
            <div class="table-responsive mb-2">
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($financing as $txn): ?>
                            <tr>
                                <td><?= format_date($txn['transaction_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?? '-') ?></td>
                                <td class="text-end"><?= format_currency($txn['amount'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
                <span>Net cash from financing</span>
                <span><?= format_currency($total_financing ?? 0) ?></span>
            </div>
        <?php endif; ?>

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
