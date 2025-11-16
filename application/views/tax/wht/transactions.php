<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">WHT Transactions</h1>
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

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <select name="month" class="form-select" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $selected_month ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == $selected_year ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Transactions</h6>
                <h5 class="mb-0"><?= $totals['transaction_count'] ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total WHT</h6>
                <h5 class="mb-0"><?= format_currency($totals['total_wht']) ?></h5>
            </div>
        </div>
    </div>
</div>

<?php if (empty($transactions)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-list-ul" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No WHT transactions found for the selected period.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>WHT Type</th>
                            <th>Beneficiary</th>
                            <th>Beneficiary TIN</th>
                            <th>Gross Amount</th>
                            <th>WHT Rate</th>
                            <th>WHT Amount</th>
                            <th>Reference</th>
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
                                <td><?= htmlspecialchars($trans['beneficiary_tin'] ?? '-') ?></td>
                                <td><?= format_currency($trans['gross_amount'] ?? 0) ?></td>
                                <td><?= number_format($trans['wht_rate'] ?? 0, 2) ?>%</td>
                                <td><strong><?= format_currency($trans['wht_amount'] ?? 0) ?></strong></td>
                                <td><?= htmlspecialchars($trans['transaction_reference'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <td colspan="6"><strong>Total</strong></td>
                            <td><strong><?= format_currency($totals['total_wht']) ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
