<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <h1 class="page-title mb-0">Billing Report</h1>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="draft" <?= $selected_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="sent" <?= $selected_status === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="paid" <?= $selected_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partial" <?= $selected_status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="overdue" <?= $selected_status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
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
                <a href="<?= base_url('utilities/reports/billing') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($bills)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No bills found for the selected criteria.</p>
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
                            <th>Billing Date</th>
                            <th>Period</th>
                            <th>Meter</th>
                            <th>Utility Type</th>
                            <th>Consumption</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bill['bill_number']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($bill['billing_date'])) ?></td>
                                <td>
                                    <?= date('M d', strtotime($bill['billing_period_start'])) ?> - 
                                    <?= date('M d, Y', strtotime($bill['billing_period_end'])) ?>
                                </td>
                                <td><?= htmlspecialchars($bill['meter_number'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($bill['utility_type_name'] ?? '-') ?></td>
                                <td>
                                    <?= number_format($bill['consumption'], 2) ?>
                                    <small class="text-muted"><?= htmlspecialchars($bill['consumption_unit'] ?? 'units') ?></small>
                                </td>
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

