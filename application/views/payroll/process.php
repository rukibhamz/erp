<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include BASEPATH . 'views/layouts/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Process Payroll</h1>
        <a href="<?= base_url('payroll') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-calculator"></i> Process Payroll</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('payroll/process') ?>">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Period (YYYY-MM) <span class="text-danger">*</span></label>
                    <input type="month" name="period" class="form-control" value="<?= htmlspecialchars($period) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Account <span class="text-danger">*</span></label>
                    <select name="cash_account_id" class="form-select" required>
                        <option value="">Select Cash Account</option>
                        <?php foreach ($cash_accounts as $account): ?>
                            <option value="<?= $account['id'] ?>">
                                <?= htmlspecialchars($account['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h5 class="mb-3">Select Employees</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleAll()" title="Select All">
                            </th>
                            <th>Employee Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Basic Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No active employees found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <?php
                                $salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
                                $basicSalary = floatval($salaryStructure['basic_salary'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="employee_ids[]" value="<?= $employee['id'] ?>" class="employee-checkbox">
                                    </td>
                                    <td><strong><?= htmlspecialchars($employee['employee_code'] ?? '-') ?></strong></td>
                                    <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                    <td><?= htmlspecialchars($employee['department'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($employee['position'] ?? '-') ?></td>
                                    <td><?= format_currency($basicSalary) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="<?= base_url('payroll') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-dark">Process Payroll</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}
</script>

<?php include BASEPATH . 'views/layouts/footer.php'; ?>

