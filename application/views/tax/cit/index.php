<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Company Income Tax (CIT)</h1>
        <?php if (hasPermission('tax', 'create')): ?>
            <a href="<?= base_url('tax/cit/calculate') ?>" class="btn btn-dark">
                <i class="bi bi-calculator"></i> Calculate CIT
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Year Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Financial Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == $selected_year ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($cit_calculation): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Profit Before Tax</h6>
                    <h5 class="mb-0"><?= format_currency($cit_calculation['profit_before_tax'] ?? 0) ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Assessable Profit</h6>
                    <h5 class="mb-0"><?= format_currency($cit_calculation['assessable_profit'] ?? 0) ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">CIT Amount</h6>
                    <h5 class="mb-0"><?= format_currency($cit_calculation['cit_amount'] ?? 0) ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Final Tax Liability</h6>
                    <h5 class="mb-0 text-danger"><?= format_currency($cit_calculation['final_tax_liability'] ?? 0) ?></h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> CIT Calculation Details</h5>
                <a href="<?= base_url('tax/cit/view/' . $cit_calculation['id']) ?>" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-eye"></i> View Details
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tr>
                    <th width="40%">Year</th>
                    <td><strong><?= $cit_calculation['year'] ?></strong></td>
                </tr>
                <tr>
                    <th>Profit Before Tax</th>
                    <td><?= format_currency($cit_calculation['profit_before_tax']) ?></td>
                </tr>
                <tr>
                    <th>Total Adjustments</th>
                    <td><?= format_currency($cit_calculation['total_adjustments']) ?></td>
                </tr>
                <tr>
                    <th>Capital Allowances</th>
                    <td><?= format_currency($cit_calculation['capital_allowances_total']) ?></td>
                </tr>
                <tr>
                    <th>Tax Reliefs</th>
                    <td><?= format_currency($cit_calculation['tax_reliefs_total']) ?></td>
                </tr>
                <tr>
                    <th>Assessable Profit</th>
                    <td><strong><?= format_currency($cit_calculation['assessable_profit']) ?></strong></td>
                </tr>
                <tr>
                    <th>CIT at 30%</th>
                    <td><?= format_currency($cit_calculation['cit_amount']) ?></td>
                </tr>
                <tr>
                    <th>Minimum Tax</th>
                    <td><?= format_currency($cit_calculation['minimum_tax']) ?></td>
                </tr>
                <tr>
                    <th>Final Tax Liability</th>
                    <td><strong class="text-danger"><?= format_currency($cit_calculation['final_tax_liability']) ?></strong></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="badge bg-<?= $cit_calculation['status'] === 'filed' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($cit_calculation['status']) ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-building" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No CIT calculation found for <?= $selected_year ?>.</p>
            <?php if (hasPermission('tax', 'create')): ?>
                <a href="<?= base_url('tax/cit/calculate') ?>" class="btn btn-dark">
                    <i class="bi bi-calculator"></i> Calculate CIT
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
