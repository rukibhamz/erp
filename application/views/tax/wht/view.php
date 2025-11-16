<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">WHT Return: <?= htmlspecialchars($wht_return['return_number'] ?? '') ?></h1>
        <a href="<?= base_url('tax/wht') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Period</h6>
                <h5 class="mb-0"><?= date('F Y', mktime(0, 0, 0, $wht_return['month'], 1, $wht_return['year'])) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total WHT</h6>
                <h5 class="mb-0"><?= format_currency($wht_return['total_wht'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Status</h6>
                <h5 class="mb-0">
                    <span class="badge bg-<?= $wht_return['status'] === 'paid' ? 'success' : ($wht_return['status'] === 'filed' ? 'info' : 'secondary') ?>">
                        <?= ucfirst($wht_return['status']) ?>
                    </span>
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Return Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="40%">Return Number</th>
                        <td><strong><?= htmlspecialchars($wht_return['return_number'] ?? 'N/A') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Period</th>
                        <td><?= date('F Y', mktime(0, 0, 0, $wht_return['month'], 1, $wht_return['year'])) ?></td>
                    </tr>
                    <tr>
                        <th>Total WHT</th>
                        <td><strong><?= format_currency($wht_return['total_wht'] ?? 0) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?= $wht_return['status'] === 'paid' ? 'success' : ($wht_return['status'] === 'filed' ? 'info' : 'secondary') ?>">
                                <?= ucfirst($wht_return['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Filing Deadline</th>
                        <td><?= date('M d, Y', strtotime($wht_return['filing_deadline'])) ?></td>
                    </tr>
                    <?php if ($wht_return['filed_date']): ?>
                        <tr>
                            <th>Filed Date</th>
                            <td><?= date('M d, Y', strtotime($wht_return['filed_date'])) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($wht_return['payment_date']): ?>
                        <tr>
                            <th>Payment Date</th>
                            <td><?= date('M d, Y', strtotime($wht_return['payment_date'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> WHT by Type</h5>
            </div>
            <div class="card-body">
                <?php if (empty($by_type)): ?>
                    <p class="text-muted mb-0">No breakdown available.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_type as $type => $amount): ?>
                                <tr>
                                    <td><?= ucfirst(str_replace('_', ' ', $type)) ?></td>
                                    <td class="text-end"><strong><?= format_currency($amount) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- WHT Transactions -->
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> WHT Transactions</h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-muted mb-0">No transactions found for this period.</p>
        <?php else: ?>
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
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($trans['date'])) ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst(str_replace('_', ' ', $trans['wht_type'] ?? 'N/A')) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($trans['beneficiary_name'] ?? 'N/A') ?></td>
                                <td><?= format_currency($trans['gross_amount'] ?? 0) ?></td>
                                <td><?= number_format($trans['wht_rate'] ?? 0, 2) ?>%</td>
                                <td><strong><?= format_currency($trans['wht_amount'] ?? 0) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="5"><strong>Total</strong></td>
                            <td><strong><?= format_currency($wht_return['total_wht'] ?? 0) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
