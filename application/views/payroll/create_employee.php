<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Employee</h1>
        <a href="<?= base_url('payroll/employees') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="employeeForm">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Employee Code</label>
                            <input type="text" name="employee_code" class="form-control">
                            <small class="text-muted">Leave empty to auto-generate</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Personal Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>

                <h5 class="mb-3">Employment Information</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Employment Type</label>
                            <select name="employment_type" class="form-select">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Hire Date</label>
                    <input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <h5 class="mb-3">Salary Structure</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Basic Salary</label>
                            <input type="number" name="basic_salary" class="form-control" step="0.01" value="0">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Allowances (JSON format)</label>
                    <textarea name="allowances_json" class="form-control" rows="4" placeholder='[{"name":"Housing","amount":500},{"name":"Transport","amount":200}]'></textarea>
                    <small class="text-muted">Enter allowances as JSON array</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deductions (JSON format)</label>
                    <textarea name="deductions_json" class="form-control" rows="4" placeholder='[{"name":"Tax","amount":100},{"name":"Pension","amount":50}]'></textarea>
                    <small class="text-muted">Enter deductions as JSON array</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('payroll/employees') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

