<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Employees</h1>
        <?php if (has_permission('payroll', 'create')): ?>
            <a href="<?= base_url('payroll/employees/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Employee
            </a>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

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
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?= htmlspecialchars($employee['employee_code'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                    <td><?= htmlspecialchars($employee['department'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($employee['position'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= ucfirst(str_replace('-', ' ', $employee['employment_type'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $employee['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($employee['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('payroll/employees/edit/' . $employee['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No employees found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

