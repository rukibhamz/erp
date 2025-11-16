<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Payroll Run: <?= htmlspecialchars($payroll_run['period'] ?? '') ?></h1>
        <div class="d-flex gap-2">
            <?php if ($payroll_run && ($payroll_run['status'] ?? '') !== 'posted' && hasPermission('payroll', 'update')): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-check-circle"></i> Post Payroll
                </button>
            <?php endif; ?>
            <a href="<?= base_url('payroll') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
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

<?php if ($payroll_run): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Period</h6>
                    <h5 class="mb-0"><?= htmlspecialchars($payroll_run['period']) ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Amount</h6>
                    <h5 class="mb-0"><?= format_currency($payroll_run['total_amount'] ?? 0) ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Status</h6>
                    <h5 class="mb-0">
                        <span class="badge bg-<?= ($payroll_run['status'] ?? '') === 'posted' ? 'success' : (($payroll_run['status'] ?? '') === 'processed' ? 'info' : 'secondary') ?>">
                            <?= ucfirst($payroll_run['status'] ?? 'draft') ?>
                        </span>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Payslips Table -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Payslips</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Basic Salary</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payslips)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No payslips found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payslips as $payslip): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payslip['employee_name'] ?? '-') ?></td>
                                    <td><?= format_currency($payslip['basic_salary'] ?? 0) ?></td>
                                    <td><?= format_currency($payslip['gross_pay'] ?? 0) ?></td>
                                    <td><?= format_currency($payslip['total_deductions'] ?? 0) ?></td>
                                    <td><strong><?= format_currency($payslip['net_pay'] ?? 0) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= ($payslip['status'] ?? 'pending') === 'paid' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($payslip['status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($payslips)): ?>
                        <tfoot class="table-primary">
                            <tr>
                                <td><strong>Total</strong></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><strong><?= format_currency($payroll_run['total_amount'] ?? 0) ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Post Payroll Modal -->
    <?php if (($payroll_run['status'] ?? '') !== 'posted' && hasPermission('payroll', 'update')): ?>
        <div class="modal fade" id="postModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="<?= base_url('payroll/post/' . $payroll_run['id']) ?>
<?php echo csrf_field(); ?>">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title"><i class="bi bi-check-circle"></i> Post Payroll</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Posting payroll will create journal entries and update cash account.</p>
                            <div class="mb-3">
                                <label class="form-label">Cash Account <span class="text-danger">*</span></label>
                                <select name="cash_account_id" class="form-select" required>
                                    <option value="">Select Account</option>
                                    <?php if (isset($cash_accounts) && !empty($cash_accounts)): ?>
                                        <?php foreach ($cash_accounts as $acc): ?>
                                            <option value="<?= $acc['id'] ?>">
                                                <?= htmlspecialchars($acc['account_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-dark">Post Payroll</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
