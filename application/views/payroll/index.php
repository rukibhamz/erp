<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Payroll</h1>
        <div class="d-flex gap-2">
            <?php if (hasPermission('payroll', 'create')): ?>
                <a href="<?= base_url('payroll/process') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Process Payroll
                </a>
            <?php endif; ?>
            <a href="<?= base_url('employees') ?>" class="btn btn-outline-dark">
                <i class="bi bi-people"></i> Employees
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
                <input type="month" name="period" class="form-control" value="<?= htmlspecialchars($selected_period ?? date('Y-m')) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-9">
                <a href="<?= base_url('payroll') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filter
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($payroll_runs)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No payroll runs found for the selected period.</p>
            <?php if (hasPermission('payroll', 'create')): ?>
                <a href="<?= base_url('payroll/process') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Process First Payroll
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Processed Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_runs as $run): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($run['period']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($run['processed_date'])) ?></td>
                                <td><?= format_currency($run['total_amount'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $run['status'] === 'posted' ? 'success' : ($run['status'] === 'processed' ? 'info' : 'secondary') ?>">
                                        <?= ucfirst($run['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('payroll/view/' . $run['id']) ?>" class="btn btn-outline-dark" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
