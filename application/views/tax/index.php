<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/tax/_nav.php');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Tax Management</h1>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Key Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon primary me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $compliance_score ?? 100 ?>%</div>
                    <div class="stat-label">Compliance Score</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon warning me-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_currency(($total_vat_payable ?? 0) + ($total_wht_payable ?? 0) + ($total_cit_payable ?? 0) + ($total_paye_payable ?? 0), 'NGN', 1) ?></div>
                    <div class="stat-label">Tax Payable</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon info me-3">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number(count($upcoming_deadlines ?? []), 0) ?></div>
                    <div class="stat-label">Upcoming Deadlines</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon danger me-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= format_large_number(count($overdue_deadlines ?? []), 0) ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-receipt"></i> VAT</h5>
                <p class="card-text">Manage VAT returns and transactions</p>
                <a href="<?= base_url('tax/vat') ?>" class="btn btn-primary">Go to VAT</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-file-earmark-minus"></i> WHT</h5>
                <p class="card-text">Withholding Tax management</p>
                <a href="<?= base_url('tax/wht') ?>" class="btn btn-primary">Go to WHT</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-building"></i> CIT</h5>
                <p class="card-text">Company Income Tax calculation</p>
                <a href="<?= base_url('tax/cit') ?>" class="btn btn-primary">Go to CIT</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent VAT Returns -->
<?php if (!empty($vat_returns)): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent VAT Returns</h5>
        </div>
        <div class="card-body">
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
                        <?php foreach (array_slice($vat_returns, 0, 5) as $return): ?>
                            <tr>
                                <td><a href="<?= base_url('tax/vat/view/' . $return['id']) ?>"><?= htmlspecialchars($return['return_number']) ?></a></td>
                                <td><?= date('M Y', strtotime($return['period_start'])) ?></td>
                                <td><?= format_currency($return['vat_payable'] ?? 0) ?></td>
                                <td><span class="badge bg-<?= $return['status'] === 'paid' ? 'success' : 'secondary' ?>"><?= ucfirst($return['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
