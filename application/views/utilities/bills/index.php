<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Utility Bills</h1>
        <a href="<?= base_url('utilities/bills/generate') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Generate Bill
        </a>
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
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="draft" <?= $selected_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="sent" <?= $selected_status === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="paid" <?= $selected_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partial" <?= $selected_status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="overdue" <?= $selected_status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('utilities/bills') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Overdue Bills Alert -->
<?php if (!empty($overdue_bills)): ?>
    <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle"></i> Overdue Bills (<?= count($overdue_bills) ?>)</h6>
        <p class="mb-0">You have <?= count($overdue_bills) ?> overdue utility bill(s) that require attention.</p>
    </div>
<?php endif; ?>

<?php if (empty($bills)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No utility bills found.</p>
            <a href="<?= base_url('utilities/bills/generate') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Generate First Bill
            </a>
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
                            <th>Utility Type</th>
                            <th>Consumption</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
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
                                <td>
                                    <?php if (!empty($bill['meter_id'])): ?>
                                        <a href="<?= base_url('utilities/meters/view/' . $bill['meter_id']) ?>">
                                            Meter #<?= $bill['meter_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted">-</span>
                                </td>
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
                                    <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                                    <?php if ($bill['status'] === 'overdue' || ($bill['due_date'] < date('Y-m-d') && $bill['balance_amount'] > 0)): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $bill['status'] === 'paid' ? 'success' : 
                                        ($bill['status'] === 'overdue' ? 'danger' : 
                                        ($bill['status'] === 'partial' ? 'warning' : 'secondary')) 
                                    ?>">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('utilities/bills/view/' . $bill['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
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

