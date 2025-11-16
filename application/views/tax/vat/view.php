<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">VAT Return: <?= htmlspecialchars($vat_return['return_number'] ?? '') ?></h1>
        <a href="<?= base_url('tax/vat') ?>" class="btn btn-primary">
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
                <h5 class="mb-0"><?= date('M d', strtotime($vat_return['period_start'])) . ' - ' . date('M d, Y', strtotime($vat_return['period_end'])) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Output VAT</h6>
                <h5 class="mb-0"><?= format_currency($vat_return['output_vat'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Input VAT</h6>
                <h5 class="mb-0"><?= format_currency($vat_return['input_vat'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Net VAT</h6>
                <h5 class="mb-0 text-danger"><?= format_currency($vat_return['net_vat'] ?? 0) ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Return Details</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <tr>
                <th width="40%">Return Number</th>
                <td><strong><?= htmlspecialchars($vat_return['return_number'] ?? 'N/A') ?></strong></td>
            </tr>
            <tr>
                <th>Period</th>
                <td><?= date('M d', strtotime($vat_return['period_start'])) . ' - ' . date('M d, Y', strtotime($vat_return['period_end'])) ?></td>
            </tr>
            <tr>
                <th>Output VAT</th>
                <td><?= format_currency($vat_return['output_vat']) ?></td>
            </tr>
            <tr>
                <th>Input VAT</th>
                <td><?= format_currency($vat_return['input_vat']) ?></td>
            </tr>
            <tr>
                <th>Net VAT</th>
                <td><strong><?= format_currency($vat_return['net_vat']) ?></strong></td>
            </tr>
            <tr>
                <th>VAT Payable</th>
                <td><strong class="text-danger"><?= format_currency($vat_return['vat_payable'] ?? 0) ?></strong></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge bg-<?= $vat_return['status'] === 'paid' ? 'success' : ($vat_return['status'] === 'filed' ? 'info' : 'secondary') ?>">
                        <?= ucfirst($vat_return['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Filing Deadline</th>
                <td><?= date('M d, Y', strtotime($vat_return['filing_deadline'])) ?></td>
            </tr>
            <?php if ($vat_return['filed_date']): ?>
                <tr>
                    <th>Filed Date</th>
                    <td><?= date('M d, Y', strtotime($vat_return['filed_date'])) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
