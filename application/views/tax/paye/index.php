<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">PAYE (Pay As You Earn)</h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('payroll') ?>" class="btn btn-primary">
                <i class="bi bi-people"></i> Go to Payroll
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

<!-- Period Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Period (YYYY-MM)</label>
                <input type="month" name="period" class="form-control" value="<?= htmlspecialchars($selected_period) ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total PAYE</h6>
                <h4 class="mb-0"><?= format_currency($totals['total_paye']) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Employees</h6>
                <h4 class="mb-0"><?= $totals['employee_count'] ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Period</h6>
                <h4 class="mb-0"><?= date('F Y', strtotime($selected_period . '-01')) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- PAYE Deductions -->
<?php if (empty($paye_deductions)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-person-badge" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No PAYE deductions found for the selected period.</p>
            <p class="text-muted small">PAYE is automatically calculated when payroll is processed. Process payroll first, then calculate PAYE.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> PAYE Deductions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Gross Income</th>
                            <th>Pension</th>
                            <th>NHF</th>
                            <th>CRA</th>
                            <th>Taxable Income</th>
                            <th>PAYE Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paye_deductions as $deduction): ?>
                            <tr>
                                <td><?= htmlspecialchars($deduction['employee_name'] ?? '-') ?></td>
                                <td><?= format_currency($deduction['gross_income'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['pension_contribution'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['nhf_contribution'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['consolidated_relief'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['taxable_income'] ?? 0) ?></td>
                                <td><strong><?= format_currency($deduction['paye_amount'] ?? 0) ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="showTaxBands(<?= htmlspecialchars(json_encode(json_decode($deduction['tax_bands_json'] ?? '[]', true))) ?>)">
                                        <i class="bi bi-info-circle"></i> Bands
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th>Total</th>
                            <th><?= format_currency(array_sum(array_column($paye_deductions, 'gross_income'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($paye_deductions, 'pension_contribution'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($paye_deductions, 'nhf_contribution'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($paye_deductions, 'consolidated_relief'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($paye_deductions, 'taxable_income'))) ?></th>
                            <th><strong><?= format_currency($totals['total_paye']) ?></strong></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- PAYE Returns -->
<?php if (!empty($returns)): ?>
    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent PAYE Returns</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Return #</th>
                            <th>Period</th>
                            <th>Total PAYE</th>
                            <th>Employees</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($return['return_number']) ?></strong></td>
                                <td><?= date('F Y', strtotime($return['period'] . '-01')) ?></td>
                                <td><?= format_currency($return['total_paye'] ?? 0) ?></td>
                                <td><?= $return['employee_count'] ?></td>
                                <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                                <td>
                                    <a href="<?= base_url('tax/paye/view/' . $return['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tax Bands Modal -->
<div class="modal fade" id="taxBandsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Tax Bands Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tax Band</th>
                            <th>Rate</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="taxBandsBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showTaxBands(bands) {
    const tbody = document.getElementById('taxBandsBody');
    tbody.innerHTML = '';
    
    bands.forEach(band => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${band.band}</td>
            <td>${band.rate}%</td>
            <td><strong>${formatCurrency(band.amount)}</strong></td>
        `;
        tbody.appendChild(row);
    });
    
    new bootstrap.Modal(document.getElementById('taxBandsModal')).show();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2
    }).format(amount);
}
</script>
