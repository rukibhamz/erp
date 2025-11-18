<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Spaces</h1>
        <a href="<?= base_url('spaces/create' . ($selected_property_id ? '/' . $selected_property_id : '')) ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Space
        </a>
    </div>
</div>

<!-- Location Management Navigation -->
<div class="Location-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('locations') ?>">
            <i class="bi bi-building"></i> Locations
        </a>
        <a class="nav-link active" href="<?= base_url('spaces') ?>">
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

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Property Filter -->
<?php if (!empty($properties)): ?>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="property_filter" class="form-label">Filter by Location</label>
                <select name="property_id" id="property_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Locations</option>
                    <?php foreach ($properties as $prop): ?>
                        <option value="<?= $prop['id'] ?>" <?= $selected_property_id == $prop['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prop['Location_name'] ?? $prop['property_name'] ?? 'N/A') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <a href="<?= base_url('spaces') ?>" class="btn btn-primary">Clear Filter</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($spaces)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-door-open" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="mt-3 text-muted">No Spaces Found</h5>
            <p class="text-muted">Create your first space to get started.</p>
            <a href="<?= base_url('spaces/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Space
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($spaces as $space): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-door-open"></i> <?= htmlspecialchars($space['space_name']) ?></h6>
                        <span class="badge bg-<?= $space['operational_status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst(str_replace('_', ' ', $space['operational_status'])) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-hash"></i> Space #: <strong><?= htmlspecialchars($space['space_number'] ?? 'N/A') ?></strong>
                            </small>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-building"></i> 
                                <?= htmlspecialchars($space['Location_name'] ?? $space['property_name'] ?? 'N/A') ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-tag"></i> 
                                <?= ucfirst(str_replace('_', ' ', $space['category'])) ?>
                            </p>
                            <p class="mb-1 text-muted small">
                                <i class="bi bi-gear"></i> 
                                Mode: <?= ucfirst(str_replace('_', ' ', $space['operational_mode'])) ?>
                            </p>
                            <?php if ($space['area']): ?>
                                <p class="mb-0 text-muted small">
                                    <i class="bi bi-rulers"></i> 
                                    <?= number_format($space['area'], 2) ?> sqm
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <?php if ($space['is_bookable']): ?>
                                <span class="badge bg-info"><i class="bi bi-calendar-check"></i> Bookable</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Not Bookable</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= base_url('spaces/edit/' . $space['id']) ?>" class="btn btn-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($space['is_bookable']): ?>
                                    <a href="<?= base_url('spaces/syncToBooking/' . $space['id']) ?>" class="btn btn-primary" title="Sync to Booking">
                                        <i class="bi bi-arrow-repeat"></i>
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

