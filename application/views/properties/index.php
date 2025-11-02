<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Properties</h1>
        <a href="<?= base_url('properties/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Property
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Property Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link active" href="<?= base_url('properties') ?>">
            <i class="bi bi-building"></i> Properties
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
.property-nav {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
}

.property-nav .nav-link {
    color: #495057;
    border: 1px solid #dee2e6;
}

.property-nav .nav-link:hover {
    background-color: #e9ecef;
    color: #000;
}

.property-nav .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}

.property-nav .nav-link i {
    margin-right: 0.5rem;
}
</style>

<div class="row g-3">
    <?php if (empty($properties)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-building" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No properties found. Create your first property to get started.</p>
                    <a href="<?= base_url('properties/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Property
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($properties as $property): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($property['property_name']) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($property['property_code']) ?></small>
                            </div>
                            <span class="badge bg-<?= $property['status'] === 'operational' ? 'success' : ($property['status'] === 'under_construction' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst(str_replace('_', ' ', $property['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-geo-alt"></i> 
                                <?= htmlspecialchars($property['city'] ?? '') ?>, <?= htmlspecialchars($property['state'] ?? '') ?>
                            </p>
                            <?php if ($property['built_area']): ?>
                                <p class="mb-1 text-muted small">
                                    <i class="bi bi-rulers"></i> 
                                    <?= number_format($property['built_area'], 2) ?> sqm
                                </p>
                            <?php endif; ?>
                            <?php if ($property['property_type']): ?>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-tag"></i> 
                                    <?= ucfirst(str_replace('_', ' ', $property['property_type'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('properties/view/' . $property['id']) ?>" class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="<?= base_url('properties/edit/' . $property['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="<?= base_url('spaces?property_id=' . $property['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-door-open"></i> Spaces
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

