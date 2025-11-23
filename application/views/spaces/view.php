<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($space['space_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('spaces/edit/' . $space['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <?php if ($space['is_bookable']): ?>
                <a href="<?= base_url('spaces/sync/' . $space['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat"></i> Sync to Booking
                </a>
            <?php endif; ?>
            <a href="<?= base_url('spaces') ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
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

<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-door-open"></i> Space Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Space Number:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($space['space_number'] ?? '-') ?></dd>
                    
                    <dt class="col-sm-4">Location:</dt>
                    <dd class="col-sm-8">
                        <a href="<?= base_url('locations/view/' . $space['property_id']) ?>">
                            <?= htmlspecialchars($space['Location_name'] ?? $space['property_name'] ?? 'N/A') ?>
                        </a>
                    </dd>
                    
                    <dt class="col-sm-4">Category:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $space['category'])) ?></dd>
                    
                    <?php if ($space['space_type']): ?>
                        <dt class="col-sm-4">Space Type:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($space['space_type']) ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $space['operational_status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst(str_replace('_', ' ', $space['operational_status'])) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Mode:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $space['operational_mode'])) ?></dd>
                    
                    <dt class="col-sm-4">Bookable:</dt>
                    <dd class="col-sm-8">
                        <?php if ($space['is_bookable']): ?>
                            <span class="badge bg-info">Yes - Synced with Booking Module</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </dd>
                    
                    <?php if ($space['area']): ?>
                        <dt class="col-sm-4">Area:</dt>
                        <dd class="col-sm-8"><?= number_format($space['area'], 2) ?> sqm</dd>
                    <?php endif; ?>
                    
                    <?php if ($space['capacity']): ?>
                        <dt class="col-sm-4">Capacity:</dt>
                        <dd class="col-sm-8"><?= $space['capacity'] ?> <?= $space['category'] === 'parking' ? 'vehicles' : 'persons' ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($space['description']): ?>
                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($space['description'])) ?></dd>
                    <?php endif; ?>
                </dl>
                
                <?php if ($space['bookable_config']): ?>
                    <hr>
                    <h6>Booking Configuration</h6>
                    <dl class="row mb-0">
                        <?php
                        $pricingRules = json_decode($space['bookable_config']['pricing_rules'] ?? '{}', true);
                        if ($pricingRules): ?>
                            <?php if (isset($pricingRules['base_hourly'])): ?>
                                <dt class="col-sm-6">Hourly Rate:</dt>
                                <dd class="col-sm-6"><?= format_currency($pricingRules['base_hourly']) ?></dd>
                            <?php endif; ?>
                            <?php if (isset($pricingRules['base_daily'])): ?>
                                <dt class="col-sm-6">Daily Rate:</dt>
                                <dd class="col-sm-6"><?= format_currency($pricingRules['base_daily']) ?></dd>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($space['bookable_config']['minimum_duration']): ?>
                            <dt class="col-sm-6">Minimum Duration:</dt>
                            <dd class="col-sm-6"><?= $space['bookable_config']['minimum_duration'] ?> hours</dd>
                        <?php endif; ?>
                        
                        <?php if ($space['bookable_config']['maximum_duration']): ?>
                            <dt class="col-sm-6">Maximum Duration:</dt>
                            <dd class="col-sm-6"><?= $space['bookable_config']['maximum_duration'] ?> hours</dd>
                        <?php endif; ?>
                    </dl>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($space['operational_mode'] === 'vacant'): ?>
                    <a href="<?= base_url('leases/create?space_id=' . $space['id']) ?>" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-file-earmark-plus"></i> Create Lease
                    </a>
                <?php endif; ?>
                
                <?php if ($space['is_bookable']): ?>
                    <a href="<?= base_url('spaces/sync/' . $space['id']) ?>" class="btn btn-info w-100 mb-2" title="Sync space with booking module">
                        <i class="bi bi-arrow-repeat"></i> Sync to Booking
                    </a>
                    <a href="<?= base_url('locations/create-booking/' . $space['property_id'] . '/' . $space['id'] . '?from=spaces') ?>" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-calendar-plus"></i> Book Space
                    </a>
                    <a href="<?= base_url('locations/booking-calendar/' . $space['property_id'] . '/' . $space['id']) ?>" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-calendar-month"></i> View Calendar
                    </a>
                <?php else: ?>
                    <div class="alert alert-info mb-2">
                        <small>This space is not bookable. Enable booking in edit mode to sync with booking module.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

