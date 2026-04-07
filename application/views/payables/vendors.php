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
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($vendor['vendor_code']) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('payables/vendors/history/' . intval($vendor['id'])) ?>">
                                        <?= htmlspecialchars($vendor['company_name']) ?>
                                    </a>
                                </td>
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
                                        <?php $role = $_SESSION['role'] ?? ''; if (in_array($role, ['super_admin', 'admin'])): ?>
                                            <form method="POST" action="<?= base_url('payables/deleteVendor/' . intval($vendor['id'])) ?>" style="display:inline" onsubmit="return confirm('Permanently delete this vendor and all associated records? This cannot be undone.')">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete Vendor">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-building"></i>
                                    <p class="mb-0">No vendors found.</p>
                                    <a href="<?= base_url('payables/vendors/create') ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create First Vendor
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

