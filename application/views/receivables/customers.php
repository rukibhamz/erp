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

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
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
                                        <a href="<?= base_url('receivables/customers/view/' . intval($customer['id'])) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (hasPermission('receivables', 'update')): ?>
                                            <a href="<?= base_url('receivables/customers/edit/' . intval($customer['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('receivables/invoices?customer_id=' . intval($customer['id'])) ?>" class="btn btn-primary" title="View Invoices">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                        <a href="<?= base_url('receivables/invoices/create?customer_id=' . intval($customer['id'])) ?>" class="btn btn-success" title="Create Invoice">
                                            <i class="bi bi-plus-circle"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p class="mb-0">No customers found.</p>
                                    <a href="<?= base_url('receivables/customers/create') ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create First Customer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

