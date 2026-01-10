<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">Cost Report</h1>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Meter</label>
                <select name="meter_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">All Meters</option>
                    <?php foreach ($meters as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($selected_meter_id ?? null) == $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['meter_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <a href="<?= base_url('utilities/reports/cost') ?>" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear Filter
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Cost</h6>
                <h2 class="mb-0"><?= format_currency($total_cost) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Paid Amount</h6>
                <h2 class="mb-0 text-success"><?= format_currency($paid_amount) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-2">Outstanding</h6>
                <h2 class="mb-0 text-danger"><?= format_currency($outstanding) ?></h2>
            </div>
        </div>
    </div>
</div>

<?php if (empty($bills)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cash-stack" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No bills found for the selected period.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Period</th>
                            <th>Meter</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bill['bill_number']) ?></strong></td>
                                <td>
                                    <?= date('M d', strtotime($bill['billing_period_start'])) ?> - 
                                    <?= date('M d, Y', strtotime($bill['billing_period_end'])) ?>
                                </td>
                                <td><?= htmlspecialchars($bill['meter_number'] ?? '-') ?></td>
                                <td><?= format_currency($bill['total_amount']) ?></td>
                                <td><?= format_currency($bill['paid_amount']) ?></td>
                                <td>
                                    <strong class="<?= floatval($bill['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($bill['balance_amount']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $bill['status'] === 'paid' ? 'success' : ($bill['status'] === 'overdue' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

