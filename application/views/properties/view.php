<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($property['property_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('spaces/create/' . $property['id']) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Space
            </a>
            <a href="<?= base_url('properties/edit/' . $property['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('properties') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

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

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Property Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Property Code:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($property['property_code'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Property Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $property['property_type'] ?? 'N/A')) ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= ($property['status'] ?? '') === 'operational' ? 'success' : (($property['status'] ?? '') === 'under_construction' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $property['status'] ?? 'N/A')) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Ownership:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $property['ownership_status'] ?? 'N/A')) ?></dd>
                    
                    <?php if (!empty($property['address'])): ?>
                        <dt class="col-sm-4">Address:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($property['address']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['city']) || !empty($property['state'])): ?>
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($property['city'] ?? '') ?><?= !empty($property['city']) && !empty($property['state']) ? ', ' : '' ?><?= htmlspecialchars($property['state'] ?? '') ?>
                            <?= !empty($property['country']) ? ', ' . htmlspecialchars($property['country']) : '' ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['postal_code'])): ?>
                        <dt class="col-sm-4">Postal Code:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($property['postal_code']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['gps_latitude']) && !empty($property['gps_longitude'])): ?>
                        <dt class="col-sm-4">GPS Coordinates:</dt>
                        <dd class="col-sm-8">
                            <?= number_format($property['gps_latitude'], 6) ?>, <?= number_format($property['gps_longitude'], 6) ?>
                            <a href="https://www.google.com/maps?q=<?= $property['gps_latitude'] ?>,<?= $property['gps_longitude'] ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="bi bi-geo-alt"></i> View on Map
                            </a>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['land_area'])): ?>
                        <dt class="col-sm-4">Land Area:</dt>
                        <dd class="col-sm-8"><?= number_format($property['land_area'], 2) ?> sqm</dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['built_area'])): ?>
                        <dt class="col-sm-4">Built Area:</dt>
                        <dd class="col-sm-8"><?= number_format($property['built_area'], 2) ?> sqm</dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['year_built'])): ?>
                        <dt class="col-sm-4">Year Built:</dt>
                        <dd class="col-sm-8"><?= $property['year_built'] ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['year_acquired'])): ?>
                        <dt class="col-sm-4">Year Acquired:</dt>
                        <dd class="col-sm-8"><?= $property['year_acquired'] ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['property_value'])): ?>
                        <dt class="col-sm-4">Property Value:</dt>
                        <dd class="col-sm-8"><?= format_currency($property['property_value']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($manager)): ?>
                        <dt class="col-sm-4">Manager:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars(trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? '')) ?: $manager['username']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Spaces Section -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Spaces (<?= count($property['spaces'] ?? []) ?>)</h5>
                <a href="<?= base_url('spaces/create/' . $property['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Space
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($property['spaces'])): ?>
                    <p class="text-muted text-center py-3">No spaces found. Add your first space.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Space #</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Mode</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($property['spaces'] as $space): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($space['space_number'] ?? '-') ?></td>
                                        <td>
                                            <a href="<?= base_url('spaces/view/' . $space['id']) ?>">
                                                <?= htmlspecialchars($space['space_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= ucfirst(str_replace('_', ' ', $space['category'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $space['operational_status'] === 'active' ? 'success' : 'warning' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $space['operational_status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= ucfirst(str_replace('_', ' ', $space['operational_mode'])) ?></td>
                                        <td>
                                            <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Total Spaces</h6>
                    <h4 class="mb-0"><?= count($property['spaces'] ?? []) ?></h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Bookable Spaces</h6>
                    <h4 class="mb-0">
                        <?= count(array_filter($property['spaces'] ?? [], function($s) { return $s['is_bookable'] == 1; })) ?>
                    </h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Leased Spaces</h6>
                    <h4 class="mb-0">
                        <?= count(array_filter($property['spaces'] ?? [], function($s) { return $s['operational_mode'] === 'leased'; })) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

