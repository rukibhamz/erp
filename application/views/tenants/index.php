<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Tenants</h1>
        <a href="<?= base_url('tenants/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Tenant
        </a>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link active" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('rent-invoices') ?>">
            <i class="bi bi-receipt"></i> Rent Invoices
        </a>
    </nav>
</div>

<style>
.Location-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.Location-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.Location-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.Location-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.Location-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

<?php endif; ?>

<?php if (empty($tenants)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Tenants Found</h5>
            <p class="text-muted">Create your first tenant to get started.</p>
            <a href="<?= base_url('tenants/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Tenant
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <?php
            $bulk_delete_enabled = has_permission('locations', 'delete');
            bulk_delete_render_toolbar($bulk_delete_enabled, $tenants, base_url('tenants/bulk-delete'), 'tenant');
            ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <?php bulk_delete_render_checkbox_th($bulk_delete_enabled); ?>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tenants as $tenant): ?>
                            <?php $tenantName = $tenant['business_name'] ?: $tenant['contact_person']; ?>
                            <tr>
                                <?php bulk_delete_render_checkbox_td($bulk_delete_enabled, (int)$tenant['id'], 'tenant ' . $tenantName); ?>
                                <td><?= htmlspecialchars($tenant['tenant_code']) ?></td>
                                <td><strong><?= htmlspecialchars($tenantName) ?></strong></td>
                                <td><?= htmlspecialchars($tenant['contact_person']) ?></td>
                                <td><?= htmlspecialchars($tenant['email']) ?></td>
                                <td><?= htmlspecialchars($tenant['phone']) ?></td>
                                <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $tenant['tenant_type'])) ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $tenant['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($tenant['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('tenants/view/' . $tenant['id']) ?>" class="btn btn-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (has_permission('locations', 'update')): ?>
                                            <a href="<?= base_url('tenants/edit/' . $tenant['id']) ?>" class="btn btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('leases/create?tenant_id=' . $tenant['id']) ?>" class="btn btn-outline-primary" title="Create Lease">
                                            <i class="bi bi-file-earmark-plus"></i>
                                        </a>
                                        <?php if (has_permission('locations', 'delete')): ?>
                                            <a href="<?= base_url('tenants/delete/' . $tenant['id']) ?>" class="btn btn-danger"
                                               title="Delete" onclick="return confirm('Are you sure you want to delete this tenant?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php render_pagination_controls($pagination ?? null); ?>
        </div>
    </div>
<?php endif; ?>

