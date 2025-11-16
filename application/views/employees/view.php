<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');

$salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
$basicSalary = $salaryStructure['basic_salary'] ?? 0;
$allowances = $salaryStructure['allowances'] ?? [];
$deductions = $salaryStructure['deductions'] ?? [];
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Employee: <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h1>
        <div class="d-flex gap-2">
            <?php if (hasPermission('payroll', 'update')): ?>
                <a href="<?= base_url('employees/edit/' . $employee['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('payroll') ?>" class="btn btn-outline-info">
                <i class="bi bi-cash-stack"></i> Process Payroll
            </a>
            <?= back_button('employees', 'Back to Employees') ?>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- Employee Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-person"></i> Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Employee Code:</th>
                        <td><strong><?= htmlspecialchars($employee['employee_code'] ?? '-') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= htmlspecialchars($employee['email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?= htmlspecialchars($employee['phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td><?= htmlspecialchars($employee['address'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Employment Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-briefcase"></i> Employment Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Department:</th>
                        <td><?= htmlspecialchars($employee['department'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Position:</th>
                        <td><?= htmlspecialchars($employee['position'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Employment Type:</th>
                        <td>
                            <span class="badge bg-info">
                                <?= ucfirst(str_replace('-', ' ', $employee['employment_type'] ?? 'N/A')) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Hire Date:</th>
                        <td><?= $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-<?= ($employee['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($employee['status'] ?? 'active') ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Salary Structure -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Salary Structure</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Basic Salary:</th>
                        <td><strong><?= format_currency($basicSalary) ?></strong></td>
                    </tr>
                    <?php if (!empty($allowances)): ?>
                        <tr>
                            <th>Allowances:</th>
                            <td>
                                <?php foreach ($allowances as $allowance): ?>
                                    <div><?= htmlspecialchars($allowance['name'] ?? '') ?>: <?= format_currency($allowance['amount'] ?? 0) ?></div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($deductions)): ?>
                        <tr>
                            <th>Deductions:</th>
                            <td>
                                <?php foreach ($deductions as $deduction): ?>
                                    <div><?= htmlspecialchars($deduction['name'] ?? '') ?>: <?= format_currency($deduction['amount'] ?? 0) ?></div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payroll History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Payroll History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($payroll_history)): ?>
                    <p class="text-muted mb-0">No payroll history found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($payroll_history, 0, 5) as $payslip): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payslip['period'] ?? '-') ?></td>
                                        <td><?= format_currency($payslip['net_pay'] ?? 0) ?></td>
                                        <td>
                                            <span class="badge bg-<?= ($payslip['status'] ?? 'draft') === 'paid' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($payslip['status'] ?? 'draft') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a href="<?= base_url('payroll') ?>" class="btn btn-sm btn-primary">View All Payroll</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

