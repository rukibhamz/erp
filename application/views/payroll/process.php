<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Process Payroll</h1>
        <a href="<?= base_url('payroll') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Payroll Processing</h5>
            </div>
            <div class="card-body">
                <?php if (isset($flash) && $flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('payroll/processPayroll') ?>">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="period" class="form-label">Pay Period <span class="text-danger">*</span></label>
                            <input type="month" class="form-control" id="period" name="period" 
                                   value="<?= htmlspecialchars($period ?? date('Y-m')) ?>" required>
                            <small class="text-muted">Select the month for payroll processing</small>
                        </div>
                        <div class="col-md-6">
                            <label for="cash_account_id" class="form-label">Cash Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="cash_account_id" name="cash_account_id" required>
                                <option value="">Select Cash Account</option>
                                <?php foreach ($cash_accounts ?? [] as $account): ?>
                                    <option value="<?= $account['id'] ?>">
                                        <?= htmlspecialchars($account['account_name']) ?> 
                                        (Balance: <?= format_currency($account['current_balance'] ?? 0) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select the cash account to pay from</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Select Employees <span class="text-danger">*</span></label>
                        <div class="card">
                            <div class="card-body">
                                <?php if (empty($employees)): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> No active employees found. 
                                        <a href="<?= base_url('payroll/employees/create') ?>">Create an employee</a> first.
                                    </div>
                                <?php else: ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="select_all" onclick="toggleAllEmployees(this)">
                                        <label class="form-check-label fw-bold" for="select_all">
                                            Select All
                                        </label>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <?php foreach ($employees as $employee): ?>
                                            <?php
                                            $salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
                                            $basicSalary = floatval($salaryStructure['basic_salary'] ?? 0);
                                            ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="form-check">
                                                            <input class="form-check-input employee-checkbox" type="checkbox" 
                                                                   name="employee_ids[]" value="<?= $employee['id'] ?>" 
                                                                   id="emp_<?= $employee['id'] ?>">
                                                            <label class="form-check-label w-100" for="emp_<?= $employee['id'] ?>">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            ID: <?= htmlspecialchars($employee['employee_code']) ?> | 
                                                                            <?= format_currency($basicSalary) ?>/month
                                                                        </small>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            <?= htmlspecialchars($employee['department'] ?? 'N/A') ?> - 
                                                                            <?= htmlspecialchars($employee['position'] ?? 'N/A') ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('payroll') ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success" <?= empty($employees) ? 'disabled' : '' ?>>
                            <i class="bi bi-check-circle"></i> Process Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAllEmployees(checkbox) {
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    employeeCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

// Update "Select All" checkbox when individual checkboxes change
document.addEventListener('DOMContentLoaded', function() {
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const selectAllCheckbox = document.getElementById('select_all');
    
    employeeCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(employeeCheckboxes).every(checkbox => checkbox.checked);
            const noneChecked = Array.from(employeeCheckboxes).every(checkbox => !checkbox.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });
});
</script>
