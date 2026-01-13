<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Locations</h1>
        <a href="<?= base_url('locations/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Location
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link <?= (strpos($_GET['url'] ?? '', 'locations') === 0 && !strpos($_GET['url'] ?? '', '/')) ? 'active' : '' ?>" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
        </a>
        <a class="nav-link" href="<?= base_url('tenants') ?>">
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

<?php if (empty($locations)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-building" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Locations Found</h5>
            <p class="text-muted">Create your first location to get started with property management.</p>
            <a href="<?= base_url('locations/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Location
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($locations as $Location): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-building"></i> <?= htmlspecialchars($Location['Location_name']) ?></h6>
                        <span class="badge bg-<?= $Location['status'] === 'operational' ? 'success' : ($Location['status'] === 'under_construction' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $Location['status'])) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-hash"></i> Code: <strong><?= htmlspecialchars($Location['Location_code']) ?></strong>
                            </small>
                            <?php if ($Location['city'] || $Location['state']): ?>
                                <p class="mb-1 text-muted small">
                                    <i class="bi bi-geo-alt"></i> 
                                    <?= htmlspecialchars($Location['city'] ?? '') ?><?= $Location['city'] && $Location['state'] ? ', ' : '' ?><?= htmlspecialchars($Location['state'] ?? '') ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($Location['built_area']): ?>
                                <p class="mb-1 text-muted small">
                                    <i class="bi bi-rulers"></i> 
                                    <?= number_format($Location['built_area'], 2) ?> sqm
                                </p>
                            <?php endif; ?>
                            <?php if ($Location['Location_type']): ?>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-tag"></i> 
                                    <?= ucfirst(str_replace('_', ' ', $Location['Location_type'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('locations/view/' . $Location['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= base_url('locations/edit/' . $Location['id']) ?>" class="btn btn-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                    <i class="bi bi-door-open"></i>
                                </a>
                                <a href="<?= base_url('locations/delete/' . $Location['id']) ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this location?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

