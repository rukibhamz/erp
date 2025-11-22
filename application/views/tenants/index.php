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
        <a class="nav-link active" href="<?= base_url('tenants') ?>">
            <i class="bi bi-people"></i> Tenants
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
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
    <div class="row g-4">
        <?php foreach ($tenants as $tenant): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-people"></i> <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?></h6>
                        <span class="badge bg-<?= $tenant['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($tenant['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-hash"></i> Code: <strong><?= htmlspecialchars($tenant['tenant_code']) ?></strong>
                            </small>
                            <p class="mb-1">
                                <strong><i class="bi bi-person"></i> Contact:</strong><br>
                                <small class="text-muted"><?= htmlspecialchars($tenant['contact_person']) ?></small>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($tenant['email']) ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($tenant['phone']) ?>
                            </p>
                            <?php if ($tenant['city'] || $tenant['state']): ?>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-geo-alt"></i> 
                                    <?= htmlspecialchars($tenant['city'] ?? '') ?><?= $tenant['city'] && $tenant['state'] ? ', ' : '' ?><?= htmlspecialchars($tenant['state'] ?? '') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $tenant['tenant_type'])) ?></span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('tenants/view/' . $tenant['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            <div class="btn-group btn-group-sm" role="group">
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
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

