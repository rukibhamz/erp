<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Vendor Utility Bills</h1>
        <a href="<?= base_url('utilities/vendor-bills/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Vendor Bill
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
                    <option value="pending" <?= $selected_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="verified" <?= $selected_status === 'verified' ? 'selected' : '' ?>>Verified</option>
                    <option value="approved" <?= $selected_status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="paid" <?= $selected_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-8">
                <a href="<?= base_url('utilities/vendor-bills') ?>" class="btn btn-primary">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($overdue_bills)): ?>
    <div class="alert alert-danger">
        <strong><i class="bi bi-exclamation-triangle"></i> Overdue Bills:</strong> 
        You have <?= count($overdue_bills) ?> overdue vendor bills that need attention.
        <a href="#overdue-section" class="alert-link">View Below</a>
    </div>
<?php endif; ?>

<?php if (empty($bills)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No vendor bills found.</p>
            <a href="<?= base_url('utilities/vendor-bills/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add First Vendor Bill
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
                            <th>Provider</th>
                            <th>Utility Type</th>
                            <th>Bill Date</th>
                            <th>Due Date</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bills as $bill): ?>
                            <tr class="<?= strtotime($bill['due_date']) < time() && $bill['status'] !== 'paid' ? 'table-danger' : '' ?>">
                                <td><strong><?= htmlspecialchars($bill['vendor_bill_number']) ?></strong></td>
                                <td><?= htmlspecialchars($bill['provider_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($bill['utility_type_name'] ?? '-') ?></td>
                                <td><?= date('M d, Y', strtotime($bill['bill_date'])) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                                    <?php if (strtotime($bill['due_date']) < time() && $bill['status'] !== 'paid'): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($bill['period_start'] && $bill['period_end']): ?>
                                        <?= date('M d', strtotime($bill['period_start'])) ?> - 
                                        <?= date('M d, Y', strtotime($bill['period_end'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= format_currency($bill['total_amount']) ?></strong></td>
                                <td>
                                    <strong class="<?= floatval($bill['balance_amount']) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($bill['balance_amount']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $bill['status'] === 'paid' ? 'success' : 
                                        ($bill['status'] === 'approved' ? 'info' : 
                                        ($bill['status'] === 'verified' ? 'primary' : 'warning')) ?>">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('utilities/vendor-bills/view/' . $bill['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($bill['status'] === 'pending' || $bill['status'] === 'verified'): ?>
                                            <a href="<?= base_url('utilities/vendor-bills/approve/' . $bill['id']) ?>" 
                                               class="btn btn-outline-success" 
                                               title="Approve"
                                               onclick="return confirm('Approve this vendor bill and post to accounting?')">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
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

<?php if (!empty($overdue_bills)): ?>
    <div class="card mt-4" id="overdue-section">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Overdue Bills</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Provider</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_bills as $bill): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bill['vendor_bill_number']) ?></strong></td>
                                <td><?= htmlspecialchars($bill['provider_name'] ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($bill['due_date'])) ?></td>
                                <td><strong><?= format_currency($bill['total_amount']) ?></strong></td>
                                <td>
                                    <?php
                                    $daysOverdue = (int)((time() - strtotime($bill['due_date'])) / 86400);
                                    ?>
                                    <span class="badge bg-danger"><?= $daysOverdue ?> days</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning"><?= ucfirst($bill['status']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= base_url('utilities/vendor-bills/view/' . $bill['id']) ?>" class="btn btn-sm btn-primary">
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

