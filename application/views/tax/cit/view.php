<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">CIT Calculation: <?= $cit_calculation['year'] ?? '' ?></h1>
        <a href="<?= base_url('tax/cit') ?>" class="btn btn-primary">
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
                <h6 class="text-muted mb-2">Year</h6>
                <h5 class="mb-0"><?= $cit_calculation['year'] ?></h5>
            </div>
        </div>
    </div>
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
                <h6 class="text-muted mb-2">Final Tax Liability</h6>
                <h5 class="mb-0 text-danger"><?= format_currency($cit_calculation['final_tax_liability'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Calculation Summary</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="50%">Profit Before Tax</th>
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
                        <th>Minimum Tax (0.5% of turnover)</th>
                        <td><?= format_currency($cit_calculation['minimum_tax']) ?></td>
                    </tr>
                    <tr class="table-danger">
                        <th><strong>Final Tax Liability</strong></th>
                        <td><strong><?= format_currency($cit_calculation['final_tax_liability']) ?></strong></td>
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
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Tax Adjustments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($adjustments)): ?>
                    <p class="text-muted mb-0">No adjustments recorded.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adjustments as $adj): ?>
                                <tr>
                                    <td><?= htmlspecialchars($adj['description'] ?? 'N/A') ?></td>
                                    <td class="text-end"><?= format_currency($adj['amount'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Adjustments</th>
                                <th class="text-end"><?= format_currency($cit_calculation['total_adjustments']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
