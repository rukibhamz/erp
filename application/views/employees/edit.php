<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');

$salaryStructure = json_decode($employee['salary_structure'] ?? '{}', true);
$basicSalary = $salaryStructure['basic_salary'] ?? 0;
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Employee: <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h1>
        <div class="d-flex gap-2">
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

<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-person-gear"></i> Employee Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('employees/edit/' . $employee['id']) ?>">
            <?php echo csrf_field(); ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Employee Code</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($employee['employee_code'] ?? '') ?>" readonly>
                    <small class="text-muted">Employee code cannot be changed</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($employee['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($employee['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3">Personal Information</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($employee['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
            </div>

            <h5 class="mb-3">Employment Information</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($employee['department'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($employee['position'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employment Type</label>
                    <select name="employment_type" class="form-select">
                        <option value="full-time" <?= ($employee['employment_type'] ?? 'full-time') === 'full-time' ? 'selected' : '' ?>>Full-time</option>
                        <option value="part-time" <?= ($employee['employment_type'] ?? '') === 'part-time' ? 'selected' : '' ?>>Part-time</option>
                        <option value="contract" <?= ($employee['employment_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Contract</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Hire Date</label>
                <input type="date" name="hire_date" class="form-control" value="<?= htmlspecialchars($employee['hire_date'] ?? date('Y-m-d')) ?>">
            </div>

            <h5 class="mb-3">Salary Structure</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Basic Salary</label>
                    <input type="number" name="basic_salary" class="form-control" step="0.01" value="<?= number_format($basicSalary, 2, '.', '') ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= base_url('employees') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Employee</button>
            </div>
        </form>
    </div>
</div>

