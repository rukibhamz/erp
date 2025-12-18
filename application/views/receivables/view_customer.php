<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Customer: <?= htmlspecialchars($customer['company_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (hasPermission('receivables', 'update')): ?>
                <a href="<?= base_url('receivables/customers/edit/' . $customer['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('receivables/invoices/create?customer_id=' . $customer['id']) ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Create Invoice
            </a>
            <a href="<?= base_url('receivables/customers') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Customer Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer Code:</strong><br>
                        <?= htmlspecialchars($customer['customer_code'] ?? 'N/A') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Company Name:</strong><br>
                        <?= htmlspecialchars($customer['company_name'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Contact Name:</strong><br>
                        <?= htmlspecialchars($customer['contact_name'] ?? '-') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong><br>
                        <?php if (!empty($customer['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($customer['email']) ?>">
                                <?= htmlspecialchars($customer['email']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Phone:</strong><br>
                        <?php if (!empty($customer['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($customer['phone']) ?>">
                                <?= htmlspecialchars($customer['phone']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Tax ID:</strong><br>
                        <?= htmlspecialchars($customer['tax_id'] ?? '-') ?>
                    </div>
                </div>
                
                <?php if (!empty($customer['address'])): ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Address:</strong><br>
                        <?= htmlspecialchars($customer['address']) ?>
                        <?php if (!empty($customer['city'])): ?>
                            <br><?= htmlspecialchars($customer['city']) ?>
                        <?php endif; ?>
                        <?php if (!empty($customer['state'])): ?>
                            , <?= htmlspecialchars($customer['state']) ?>
                        <?php endif; ?>
                        <?php if (!empty($customer['zip_code'])): ?>
                            <?= htmlspecialchars($customer['zip_code']) ?>
                        <?php endif; ?>
                        <?php if (!empty($customer['country'])): ?>
                            <br><?= htmlspecialchars($customer['country']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Currency:</strong><br>
                        <?= htmlspecialchars($customer['currency'] ?? 'NGN') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?= ($customer['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($customer['status'] ?? 'inactive') ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer Type:</strong><br>
                        <span class="badge bg-info">
                            <?php 
                            if (!empty($customer['customer_type_name'])) {
                                echo htmlspecialchars($customer['customer_type_name']) . ' (' . ($customer['discount_percentage'] ?? 0) . '%)';
                            } else {
                                echo 'Standard / Retail';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Credit Limit:</strong><br>
                        <?= format_currency($customer['credit_limit'] ?? 0, $customer['currency'] ?? 'NGN') ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Payment Terms:</strong><br>
                        <?= htmlspecialchars($customer['payment_terms'] ?? '-') ?>
                    </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Outstanding Balance:</strong><br>
                        <span class="fs-4 <?= ($outstanding ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= format_currency($outstanding ?? 0, $customer['currency'] ?? 'NGN') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($invoices)): ?>
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Invoices</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
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
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($invoice['invoice_number'] ?? 'N/A') ?></strong></td>
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
                                            <a href="<?= base_url('receivables/invoices/view/' . intval($invoice['id'])) ?>" class="btn btn-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (hasPermission('receivables', 'update')): ?>
                                                <a href="<?= base_url('receivables/invoices/edit/' . intval($invoice['id'])) ?>" class="btn btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
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
    </div>
</div>
<?php endif; ?>

