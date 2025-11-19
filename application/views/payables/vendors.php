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
                                        <?php if (hasPermission('payables', 'update')): ?>
                                            <a href="<?= base_url('payables/vendors/edit/' . intval($vendor['id'])) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('payables/bills?vendor_id=' . intval($vendor['id'])) ?>" class="btn btn-primary" title="View Bills">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                        <a href="<?= base_url('payables/bills/create?vendor_id=' . intval($vendor['id'])) ?>" class="btn btn-success" title="Create Bill">
                                            <i class="bi bi-plus-circle"></i>
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

