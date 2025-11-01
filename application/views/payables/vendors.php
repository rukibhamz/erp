<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Vendors</h1>
        <a href="<?= base_url('payables/vendors/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Vendor
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
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($vendor['vendor_code']) ?></strong></td>
                                <td><?= htmlspecialchars($vendor['company_name']) ?></td>
                                <td><?= htmlspecialchars($vendor['contact_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($vendor['email'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($vendor['phone'] ?? '-') ?></td>
                                <td class="text-end">
                                    <strong><?= format_currency($vendor['outstanding'] ?? 0) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $vendor['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($vendor['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('payables/vendors/edit/' . $vendor['id']) ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= base_url('payables/bills?vendor_id=' . $vendor['id']) ?>" class="btn btn-outline-info" title="View Bills">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No vendors found. <a href="<?= base_url('payables/vendors/create') ?>">Create your first vendor</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

