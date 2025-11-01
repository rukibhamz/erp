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

<!-- Accounting Navigation -->
<div class="accounting-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('accounting') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="<?= base_url('accounts') ?>">
            <i class="bi bi-diagram-3"></i> Chart of Accounts
        </a>
        <a class="nav-link" href="<?= base_url('cash') ?>">
            <i class="bi bi-wallet2"></i> Cash Management
        </a>
        <a class="nav-link" href="<?= base_url('receivables') ?>">
            <i class="bi bi-receipt"></i> Receivables
        </a>
        <a class="nav-link active" href="<?= base_url('payables') ?>">
            <i class="bi bi-file-earmark-medical"></i> Payables
        </a>
        <a class="nav-link" href="<?= base_url('ledger') ?>">
            <i class="bi bi-journal-text"></i> General Ledger
        </a>
    </nav>
</div>

<style>
.accounting-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.accounting-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.accounting-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.accounting-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.accounting-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

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
                                <td class="text-end"><strong><?= format_currency($bill['total_amount'] ?? 0, $bill['currency'] ?? 'USD') ?></strong></td>
                                <td class="text-end"><?= format_currency(($bill['total_amount'] ?? 0) - ($bill['balance_amount'] ?? 0), $bill['currency'] ?? 'USD') ?></td>
                                <td class="text-end">
                                    <strong class="<?= ($bill['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($bill['balance_amount'] ?? 0, $bill['currency'] ?? 'USD') ?>
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
                                        <a href="<?= base_url('payables/bills/edit/' . $bill['id']) ?>" class="btn btn-outline-secondary" title="View/Edit">
                                            <i class="bi bi-eye"></i>
                                        </a>
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

