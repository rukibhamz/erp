<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Payroll Run: <?= htmlspecialchars($payroll_run['period'] ?? '') ?></h1>
        <a href="<?= base_url('payroll') ?>" class="btn btn-primary">
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

<?php if (!$payroll_run): ?>
    <div class="alert alert-danger">
        Payroll run not found.
    </div>
<?php else: ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Period</h6>
                <h4><?= htmlspecialchars($payroll_run['period']) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Status</h6>
                <h4>
                    <span class="badge bg-<?= $payroll_run['status'] === 'posted' ? 'success' : 'warning' ?>">
                        <?= ucfirst($payroll_run['status']) ?>
                    </span>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Total Amount</h6>
                <h4><?= format_currency($payroll_run['total_amount'] ?? 0) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Processed Date</h6>
                <h4><?= date('M d, Y', strtotime($payroll_run['processed_date'] ?? 'now')) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Payslips</h5>
    </div>
    <div class="card-body">
        <?php if (empty($payslips)): ?>
            <p class="text-center text-muted py-4">No payslips found for this payroll run.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th class="text-end">Basic Salary</th>
                            <th class="text-end">Gross Pay</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payslips as $payslip): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($payslip['employee_name'] ?? 'Unknown') ?></strong>
                                </td>
                                <td class="text-end"><?= format_currency($payslip['basic_salary'] ?? 0) ?></td>
                                <td class="text-end"><?= format_currency($payslip['gross_pay'] ?? 0) ?></td>
                                <td class="text-end"><?= format_currency($payslip['total_deductions'] ?? 0) ?></td>
                                <td class="text-end"><strong><?= format_currency($payslip['net_pay'] ?? 0) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $payslip['status'] === 'posted' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($payslip['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('payroll/payslip/' . $payslip['id']) ?>" class="btn btn-sm btn-primary" title="View Payslip">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th>Total</th>
                            <th class="text-end"><?= format_currency(array_sum(array_column($payslips, 'basic_salary'))) ?></th>
                            <th class="text-end"><?= format_currency(array_sum(array_column($payslips, 'gross_pay'))) ?></th>
                            <th class="text-end"><?= format_currency(array_sum(array_column($payslips, 'total_deductions'))) ?></th>
                            <th class="text-end"><strong><?= format_currency(array_sum(array_column($payslips, 'net_pay'))) ?></strong></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($payroll_run['status'] !== 'posted' && !empty($cash_accounts)): ?>
    <div class="card mt-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Post Payroll</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= base_url('payroll/post/' . $payroll_run['id']) ?>">
                <?php echo csrf_field(); ?>
                <div class="row">
                    <div class="col-md-6">
                        <label for="cash_account_id" class="form-label">Cash Account <span class="text-danger">*</span></label>
                        <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                            <option value="">Select Cash Account</option>
                            <?php foreach ($cash_accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= htmlspecialchars($account['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Post Payroll
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php endif; ?>
