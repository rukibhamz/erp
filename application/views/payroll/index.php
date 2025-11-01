<?php $this->load->view('layouts/header', $data); ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payroll Management</h1>
        <div>
            <?php if (has_permission('payroll', 'create')): ?>
                <a href="<?= base_url('payroll/process') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Process Payroll
                </a>
            <?php endif; ?>
            <a href="<?= base_url('payroll/employees') ?>" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Employees
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Period (YYYY-MM)</label>
                    <input type="month" name="period" class="form-control" value="<?= htmlspecialchars($selected_period) ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <!-- Payroll Runs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Processed Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payroll_runs)): ?>
                            <?php foreach ($payroll_runs as $run): ?>
                                <tr>
                                    <td><?= htmlspecialchars($run['period']) ?></td>
                                    <td><?= date('M d, Y', strtotime($run['processed_date'])) ?></td>
                                    <td><?= format_currency($run['total_amount'] ?? 0, 'USD') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $run['status'] === 'posted' ? 'success' : ($run['status'] === 'processed' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($run['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('payroll/view/' . $run['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No payroll runs found for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('layouts/footer'); ?>

