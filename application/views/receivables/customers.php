<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Customers</h1>
        <a href="<?= base_url('receivables/customers/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Customer
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/accounting/_nav.php'); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Company Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($customer['customer_code']) ?></strong></td>
                                <td><?= htmlspecialchars($customer['company_name']) ?></td>
                                <td><?= htmlspecialchars($customer['contact_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($customer['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                                <td class="text-end">
                                    <strong><?= format_currency($customer['outstanding'] ?? 0) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $customer['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($customer['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('receivables/customers/edit/' . $customer['id']) ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('receivables/invoices?customer_id=' . $customer['id']) ?>" class="btn btn-outline-info" title="View Invoices">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No customers found. <a href="<?= base_url('receivables/customers/create') ?>">Create your first customer</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

