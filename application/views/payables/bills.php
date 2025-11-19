<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Bills</h1>
        <a href="<?= base_url('payables/bills/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Create Bill
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('payables/bills') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="received" <?= ($selected_status ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                    <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partially_paid" <?= ($selected_status ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="overdue" <?= ($selected_status ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="vendor_id" class="form-label">Vendor</label>
                <select class="form-select" id="vendor_id" name="vendor_id">
                    <option value="">All Vendors</option>
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?= $vendor['id'] ?>" <?= (($selected_vendor ?? '') == $vendor['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vendor['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= base_url('payables/bills') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bills Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">All Bills</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills)): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bill['bill_number'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($bill['company_name'] ?? '-') ?></td>
                                <td><?= format_date($bill['bill_date'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $dueDate = $bill['due_date'] ?? '';
                                    $isOverdue = $dueDate && strtotime($dueDate) < time() && ($bill['status'] ?? '') !== 'paid';
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= format_date($dueDate) ?>
                                    </span>
                                </td>
                                <td class="text-end"><strong><?= format_currency($bill['total_amount'] ?? 0, $bill['currency'] ?? 'NGN') ?></strong></td>
                                <td class="text-end"><?= format_currency(($bill['total_amount'] ?? 0) - ($bill['balance_amount'] ?? 0), $bill['currency'] ?? 'NGN') ?></td>
                                <td class="text-end">
                                    <strong class="<?= ($bill['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($bill['balance_amount'] ?? 0, $bill['currency'] ?? 'NGN') ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => 'secondary',
                                        'received' => 'info',
                                        'paid' => 'success',
                                        'partially_paid' => 'warning',
                                        'overdue' => 'danger'
                                    ];
                                    $badgeColor = $statusBadges[$bill['status'] ?? 'draft'] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $bill['status'] ?? 'draft')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('payables/bills/view/' . $bill['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('payables', 'update')): ?>
                                            <a href="<?= base_url('payables/bills/edit/' . $bill['id']) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No bills found. <a href="<?= base_url('payables/bills/create') ?>">Create your first bill</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

