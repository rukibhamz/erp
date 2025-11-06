<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Invoices</h1>
        <a href="<?= base_url('receivables/invoices/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Create Invoice
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= base_url('receivables/invoices') ?>" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($selected_status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="sent" <?= ($selected_status ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="paid" <?= ($selected_status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partially_paid" <?= ($selected_status ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="overdue" <?= ($selected_status ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="customer_id" class="form-label">Customer</label>
                <select class="form-select" id="customer_id" name="customer_id">
                    <option value="">All Customers</option>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>" <?= (($selected_customer ?? '') == $customer['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['company_name']) ?>
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
                <a href="<?= base_url('receivables/invoices') ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">All Invoices</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
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
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($invoice['company_name'] ?? '-') ?></td>
                                <td><?= format_date($invoice['invoice_date'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $dueDate = $invoice['due_date'] ?? '';
                                    $isOverdue = $dueDate && strtotime($dueDate) < time() && ($invoice['status'] ?? '') !== 'paid';
                                    ?>
                                    <span class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                        <?= format_date($dueDate) ?>
                                    </span>
                                </td>
                                <td class="text-end"><strong><?= format_currency($invoice['total_amount'] ?? 0, $invoice['currency'] ?? 'NGN') ?></strong></td>
                                <td class="text-end"><?= format_currency($invoice['paid_amount'] ?? 0, $invoice['currency'] ?? 'NGN') ?></td>
                                <td class="text-end">
                                    <strong class="<?= ($invoice['balance_amount'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= format_currency($invoice['balance_amount'] ?? 0, $invoice['currency'] ?? 'NGN') ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'paid' => 'success',
                                        'partially_paid' => 'warning',
                                        'overdue' => 'danger'
                                    ];
                                    $badgeColor = $statusBadges[$invoice['status'] ?? 'draft'] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $invoice['status'] ?? 'draft')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('receivables/invoices/edit/' . $invoice['id']) ?>" class="btn btn-outline-secondary" title="View/Edit">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (($invoice['balance_amount'] ?? 0) > 0): ?>
                                            <a href="<?= base_url('receivables/invoices/payment/' . $invoice['id']) ?>" class="btn btn-outline-success" title="Record Payment">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No invoices found. <a href="<?= base_url('receivables/invoices/create') ?>">Create your first invoice</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

