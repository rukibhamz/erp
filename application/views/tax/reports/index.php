<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tax Reports</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="<?= base_url('tax/reports/export?type=' . $report_type . '&start_date=' . $start_date . '&end_date=' . $end_date . '&format=pdf') ?>" class="btn btn-outline-dark">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Report Type Selector and Date Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="summary" <?= $report_type === 'summary' ? 'selected' : '' ?>>Summary Report</option>
                    <option value="vat" <?= $report_type === 'vat' ? 'selected' : '' ?>>VAT Report</option>
                    <option value="wht" <?= $report_type === 'wht' ? 'selected' : '' ?>>WHT Report</option>
                    <option value="cit" <?= $report_type === 'cit' ? 'selected' : '' ?>>CIT Report</option>
                    <option value="payments" <?= $report_type === 'payments' ? 'selected' : '' ?>>Payments Report</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-dark w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php if ($report_type === 'summary'): ?>
    <!-- Summary Report -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">VAT Payable</h6>
                    <h4 class="mb-0"><?= format_currency($total_vat_payable ?? 0) ?></h4>
                    <small class="text-muted">Paid: <?= format_currency($total_vat_paid ?? 0) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">WHT Payable</h6>
                    <h4 class="mb-0"><?= format_currency($total_wht_payable ?? 0) ?></h4>
                    <small class="text-muted">Paid: <?= format_currency($total_wht_paid ?? 0) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">CIT Payable</h6>
                    <h4 class="mb-0"><?= format_currency($total_cit_payable ?? 0) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Payments</h6>
                    <h4 class="mb-0"><?= format_currency($total_payments ?? 0) ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Recent VAT Returns</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($vat_returns)): ?>
                        <p class="text-muted mb-0">No VAT returns found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Return #</th>
                                        <th>Period</th>
                                        <th>VAT Payable</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vat_returns as $return): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($return['return_number']) ?></td>
                                            <td><?= date('M Y', strtotime($return['period_start'])) ?></td>
                                            <td><?= format_currency($return['vat_payable'] ?? 0) ?></td>
                                            <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Recent WHT Returns</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($wht_returns)): ?>
                        <p class="text-muted mb-0">No WHT returns found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Return #</th>
                                        <th>Period</th>
                                        <th>Total WHT</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wht_returns as $return): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($return['return_number']) ?></td>
                                            <td><?= date('F Y', mktime(0, 0, 0, $return['month'], 1, $return['year'])) ?></td>
                                            <td><?= format_currency($return['total_wht'] ?? 0) ?></td>
                                            <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($report_type === 'vat'): ?>
    <!-- VAT Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">VAT Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Output VAT</h6>
                            <h4 class="mb-0"><?= format_currency($total_output_vat ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Input VAT</h6>
                            <h4 class="mb-0"><?= format_currency($total_input_vat ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Net VAT</h6>
                            <h4 class="mb-0"><?= format_currency($total_net_vat ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Payable</h6>
                            <h4 class="mb-0 text-danger"><?= format_currency($total_payable ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($vat_returns)): ?>
                <p class="text-muted">No VAT returns found for the selected period.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Return #</th>
                                <th>Period</th>
                                <th>Output VAT</th>
                                <th>Input VAT</th>
                                <th>Net VAT</th>
                                <th>VAT Payable</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vat_returns as $return): ?>
                                <tr>
                                    <td><?= htmlspecialchars($return['return_number']) ?></td>
                                    <td><?= date('M d', strtotime($return['period_start'])) . ' - ' . date('M d, Y', strtotime($return['period_end'])) ?></td>
                                    <td><?= format_currency($return['output_vat'] ?? 0) ?></td>
                                    <td><?= format_currency($return['input_vat'] ?? 0) ?></td>
                                    <td><?= format_currency($return['net_vat'] ?? 0) ?></td>
                                    <td><strong><?= format_currency($return['vat_payable'] ?? 0) ?></strong></td>
                                    <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th colspan="2">Total</th>
                                <th><?= format_currency($total_output_vat ?? 0) ?></th>
                                <th><?= format_currency($total_input_vat ?? 0) ?></th>
                                <th><?= format_currency($total_net_vat ?? 0) ?></th>
                                <th><?= format_currency($total_payable ?? 0) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($report_type === 'wht'): ?>
    <!-- WHT Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">WHT Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total WHT</h6>
                            <h4 class="mb-0"><?= format_currency($total_wht ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Transactions</h6>
                            <h4 class="mb-0"><?= $transaction_count ?? 0 ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Returns</h6>
                            <h4 class="mb-0"><?= count($wht_returns ?? []) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($wht_returns)): ?>
                <h5 class="mb-3">WHT Returns</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Return #</th>
                                <th>Period</th>
                                <th>Total WHT</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wht_returns as $return): ?>
                                <tr>
                                    <td><?= htmlspecialchars($return['return_number']) ?></td>
                                    <td><?= date('F Y', mktime(0, 0, 0, $return['month'], 1, $return['year'])) ?></td>
                                    <td><?= format_currency($return['total_wht'] ?? 0) ?></td>
                                    <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($wht_transactions)): ?>
                <h5 class="mb-3">WHT Transactions</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>WHT Type</th>
                                <th>Beneficiary</th>
                                <th>Gross Amount</th>
                                <th>WHT Rate</th>
                                <th>WHT Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wht_transactions as $trans): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($trans['date'])) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $trans['wht_type'] ?? 'N/A')) ?></td>
                                    <td><?= htmlspecialchars($trans['beneficiary_name'] ?? 'N/A') ?></td>
                                    <td><?= format_currency($trans['gross_amount'] ?? 0) ?></td>
                                    <td><?= number_format($trans['wht_rate'] ?? 0, 2) ?>%</td>
                                    <td><strong><?= format_currency($trans['wht_amount'] ?? 0) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($report_type === 'cit'): ?>
    <!-- CIT Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">CIT Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total CIT Liability</h6>
                            <h4 class="mb-0 text-danger"><?= format_currency($total_liability ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Calculations</h6>
                            <h4 class="mb-0"><?= count($cit_calculations ?? []) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($cit_calculations)): ?>
                <p class="text-muted">No CIT calculations found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Profit Before Tax</th>
                                <th>Assessable Profit</th>
                                <th>CIT Amount</th>
                                <th>Minimum Tax</th>
                                <th>Final Liability</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cit_calculations as $calc): ?>
                                <tr>
                                    <td><strong><?= $calc['year'] ?></strong></td>
                                    <td><?= format_currency($calc['profit_before_tax'] ?? 0) ?></td>
                                    <td><?= format_currency($calc['assessable_profit'] ?? 0) ?></td>
                                    <td><?= format_currency($calc['cit_amount'] ?? 0) ?></td>
                                    <td><?= format_currency($calc['minimum_tax'] ?? 0) ?></td>
                                    <td><strong class="text-danger"><?= format_currency($calc['final_tax_liability'] ?? 0) ?></strong></td>
                                    <td><span class="badge bg-<?= $calc['status'] === 'filed' ? 'success' : 'secondary' ?>"><?= ucfirst($calc['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th colspan="5">Total</th>
                                <th><?= format_currency($total_liability ?? 0) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($report_type === 'payments'): ?>
    <!-- Payments Report -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Tax Payments Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Payments</h6>
                            <h4 class="mb-0"><?= format_currency($total_payments ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Payment Count</h6>
                            <h4 class="mb-0"><?= count($payments ?? []) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($by_type)): ?>
                <h5 class="mb-3">Payments by Tax Type</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tax Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_type as $type => $amount): ?>
                                <tr>
                                    <td><?= htmlspecialchars($type) ?></td>
                                    <td class="text-end"><strong><?= format_currency($amount) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (empty($payments)): ?>
                <p class="text-muted">No payments found for the selected period.</p>
            <?php else: ?>
                <h5 class="mb-3">Payment Details</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tax Type</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($payment['tax_type']) ?></span></td>
                                    <td><strong><?= format_currency($payment['amount'] ?? 0) ?></strong></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'] ?? 'N/A')) ?></td>
                                    <td><?= htmlspecialchars($payment['reference'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th colspan="2">Total</th>
                                <th><?= format_currency($total_payments ?? 0) ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<style>
@media print {
    .page-header, .card.mb-4, .btn, .alert { display: none !important; }
    .card { border: 1px solid #ddd; page-break-inside: avoid; }
}
</style>
