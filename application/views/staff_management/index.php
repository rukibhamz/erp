<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/staff_management/_nav.php');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Staff Management Dashboard</h1>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Total Employees</h6>
                        <h3 class="card-title mb-0 fw-bold"><?= format_large_number($total_employees ?? 0, 0) ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-people" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Monthly Payroll</h6>
                        <h3 class="card-title mb-0 fw-bold text-info"><?= format_large_currency($monthly_payroll_total ?? 0, 'NGN', 1) ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-cash-stack" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Payroll Runs</h6>
                        <h3 class="card-title mb-0 fw-bold text-success"><?= format_large_number($total_payroll_runs ?? 0, 0) ?></h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-calendar-check" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-subtitle text-muted mb-1 small text-uppercase">Pending Payroll</h6>
                        <h3 class="card-title mb-0 fw-bold text-warning"><?= format_large_number($pending_payroll ?? 0, 0) ?></h3>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-clock-history" style="font-size: 2rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= base_url('staff_management/employees') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-people me-2"></i> Manage Employees
                    </a>
                    <a href="<?= base_url('staff_management/payroll') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-cash-stack me-2"></i> View Payroll
                    </a>
                    <?php if (hasPermission('staff_management', 'create')): ?>
                        <a href="<?= base_url('staff_management/payroll/process') ?>" class="btn btn-outline-success">
                            <i class="bi bi-plus-circle me-2"></i> Process Payroll
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">No recent activity</p>
            </div>
        </div>
    </div>
</div>

