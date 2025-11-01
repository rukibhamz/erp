<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payroll Run: <?= htmlspecialchars($payroll_run['period'] ?? '') ?></h1>
        <div>
            <?php if ($payroll_run && $payroll_run['status'] !== 'posted' && has_permission('payroll', 'update')): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-check-circle"></i> Post Payroll
                </button>
            <?php endif; ?>
            <a href="<?= base_url('payroll') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($payroll_run): ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Period</h6>
                        <h5><?= htmlspecialchars($payroll_run['period']) ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Total Amount</h6>
                        <h5><?= format_currency($payroll_run['total_amount'] ?? 0, 'USD') ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">Status</h6>
                        <h5>
                            <span class="badge bg-<?= $payroll_run['status'] === 'posted' ? 'success' : ($payroll_run['status'] === 'processed' ? 'info' : 'secondary') ?>">
                                <?= ucfirst($payroll_run['status']) ?>
                            </span>
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payslips Table -->
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Payslips</h5>
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
                            <?php if (!empty($payslips)): ?>
                                <?php foreach ($payslips as $payslip): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payslip['employee_name'] ?? '-') ?></td>
                                        <td><?= format_currency($payslip['basic_salary'] ?? 0, 'USD') ?></td>
                                        <td><?= format_currency($payslip['gross_pay'] ?? 0, 'USD') ?></td>
                                        <td><?= format_currency($payslip['total_deductions'] ?? 0, 'USD') ?></td>
                                        <td><strong><?= format_currency($payslip['net_pay'] ?? 0, 'USD') ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?= $payslip['status'] === 'paid' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($payslip['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payslips found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <td><strong>Total</strong></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td><strong><?= format_currency($payroll_run['total_amount'] ?? 0, 'USD') ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Post Payroll Modal -->
        <?php if ($payroll_run['status'] !== 'posted' && has_permission('payroll', 'update')): ?>
            <div class="modal fade" id="postModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="<?= base_url('payroll/post/' . $payroll_run['id']) ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Post Payroll</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Posting payroll will create journal entries and update cash account.</p>
                                <div class="mb-3">
                                    <label class="form-label">Cash Account *</label>
                                    <select name="cash_account_id" class="form-select" required>
                                        <option value="">Select Account</option>
                                        <?php
                                        try {
                                            $cashAccountModel = new Cash_account_model();
                                            $cashAccounts = $cashAccountModel->getActive();
                                            foreach ($cashAccounts as $acc):
                                        ?>
                                            <option value="<?= $acc['id'] ?>">
                                                <?= htmlspecialchars($acc['account_name']) ?>
                                            </option>
                                        <?php
                                            endforeach;
                                        } catch (Exception $e) {}
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Post Payroll</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php $this->load->view('layouts/footer'); ?>

