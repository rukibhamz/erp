<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">PAYE Return: <?= htmlspecialchars($paye_return['return_number'] ?? '') ?></h1>
        <a href="<?= base_url('tax/paye') ?>" class="btn btn-primary">
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
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Period</h6>
                <h5 class="mb-0"><?= date('F Y', strtotime($paye_return['period'] . '-01')) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total PAYE</h6>
                <h5 class="mb-0 text-danger"><?= format_currency($paye_return['total_paye'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Employees</h6>
                <h5 class="mb-0"><?= $paye_return['employee_count'] ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Status</h6>
                <h5 class="mb-0">
                    <span class="badge bg-<?= $paye_return['status'] === 'paid' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($paye_return['status']) ?>
                    </span>
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> PAYE Deductions by Employee</h5>
    </div>
    <div class="card-body">
        <?php if (empty($deductions)): ?>
            <p class="text-muted mb-0">No deductions found.</p>
        <?php else: ?>
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
                            <th>Tax Calculated</th>
                            <th>Minimum Tax</th>
                            <th>PAYE Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deductions as $deduction): ?>
                            <tr>
                                <td><?= htmlspecialchars($deduction['employee_name'] ?? '-') ?></td>
                                <td><?= format_currency($deduction['gross_income'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['pension_contribution'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['nhf_contribution'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['consolidated_relief'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['taxable_income'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['tax_calculated'] ?? 0) ?></td>
                                <td><?= format_currency($deduction['minimum_tax'] ?? 0) ?></td>
                                <td><strong><?= format_currency($deduction['paye_amount'] ?? 0) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th>Total</th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'gross_income'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'pension_contribution'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'nhf_contribution'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'consolidated_relief'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'taxable_income'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'tax_calculated'))) ?></th>
                            <th><?= format_currency(array_sum(array_column($deductions, 'minimum_tax'))) ?></th>
                            <th><strong><?= format_currency($paye_return['total_paye']) ?></strong></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>



