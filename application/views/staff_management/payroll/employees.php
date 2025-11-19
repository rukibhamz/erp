<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include(BASEPATH . 'views/accounting/_nav.php');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Employees</h1>
        <?php if (hasPermission('payroll', 'create')): ?>
            <a href="<?= base_url('payroll/employees/create') ?>" class="btn btn-dark">
                <i class="bi bi-plus-circle"></i> Add Employee
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($employees)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No employees found.</p>
            <?php if (hasPermission('payroll', 'create')): ?>
                <a href="<?= base_url('payroll/employees/create') ?>" class="btn btn-dark">
                    <i class="bi bi-plus-circle"></i> Add First Employee
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
                            <th>Employee Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($employee['employee_code'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                <td><?= htmlspecialchars($employee['department'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($employee['position'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= ucfirst(str_replace('-', ' ', $employee['employment_type'] ?? 'N/A')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($employee['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($employee['status'] ?? 'active') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('payroll/employees/edit/' . $employee['id']) ?>" class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
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
