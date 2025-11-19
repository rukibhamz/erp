<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Process Payroll</h1>
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

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_url('payroll/process') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-4">
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
                                (<?= format_currency($account['balance'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Select Employees <span class="text-danger">*</span></label>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($employees)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No active employees found. 
                                <a href="<?= base_url('employees/create') ?>">Create an employee first</a>.
                            </div>
                        <?php else: ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll">
                                    <strong>Select All</strong>
                                </label>
                            </div>
                            <hr>
                            <div class="row">
                                <?php foreach ($employees as $employee): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input employee-checkbox" type="checkbox" 
                                                   name="employee_ids[]" value="<?= $employee['id'] ?>" 
                                                   id="employee_<?= $employee['id'] ?>">
                                            <label class="form-check-label" for="employee_<?= $employee['id'] ?>">
                                                <strong><?= htmlspecialchars($employee['employee_name'] ?? $employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    ID: <?= htmlspecialchars($employee['employee_code'] ?? $employee['id']) ?> | 
                                                    <?php
                                                    $salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
                                                    $basicSalary = floatval($salaryStructure['basic_salary'] ?? 0);
                                                    echo format_currency($basicSalary);
                                                    ?> /month
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('payroll') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary" <?= empty($employees) ? 'disabled' : '' ?>>
                    <i class="bi bi-check-circle"></i> Process Payroll
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            employeeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all when individual checkboxes change
        employeeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(employeeCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(employeeCheckboxes).some(cb => cb.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });
    }
});
</script>

