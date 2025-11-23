<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0"><?= htmlspecialchars($Location['Location_name']) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= base_url('spaces/create/' . $Location['id']) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Space
            </a>
            <a href="<?= base_url('locations/edit/' . $Location['id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="<?= base_url('locations') ?>" class="btn btn-primary">
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
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-building"></i> Location Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Location Code:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($Location['Location_code'] ?? $Location['property_code'] ?? 'N/A') ?></dd>
                    
                    <dt class="col-sm-4">Location Type:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $Location['Location_type'] ?? $Location['property_type'] ?? 'N/A')) ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= ($Location['status'] ?? '') === 'operational' ? 'success' : (($Location['status'] ?? '') === 'under_construction' ? 'warning' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $Location['status'] ?? 'N/A')) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Ownership:</dt>
                    <dd class="col-sm-8"><?= ucfirst(str_replace('_', ' ', $Location['ownership_status'] ?? 'N/A')) ?></dd>
                    
                    <?php if (!empty($Location['address'])): ?>
                        <dt class="col-sm-4">Address:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($Location['address']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['city']) || !empty($Location['state'])): ?>
                        <dt class="col-sm-4">Location:</dt>
                        <dd class="col-sm-8">
                            <?= htmlspecialchars($Location['city'] ?? '') ?><?= !empty($Location['city']) && !empty($Location['state']) ? ', ' : '' ?><?= htmlspecialchars($Location['state'] ?? '') ?>
                            <?= !empty($Location['country']) ? ', ' . htmlspecialchars($Location['country']) : '' ?>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['postal_code'])): ?>
                        <dt class="col-sm-4">Postal Code:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($Location['postal_code']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['gps_latitude']) && !empty($Location['gps_longitude'])): ?>
                        <dt class="col-sm-4">GPS Coordinates:</dt>
                        <dd class="col-sm-8">
                            <?= number_format($Location['gps_latitude'], 6) ?>, <?= number_format($Location['gps_longitude'], 6) ?>
                            <a href="https://www.google.com/maps?q=<?= $Location['gps_latitude'] ?>,<?= $Location['gps_longitude'] ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="bi bi-geo-alt"></i> View on Map
                            </a>
                        </dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['land_area'])): ?>
                        <dt class="col-sm-4">Land Area:</dt>
                        <dd class="col-sm-8"><?= number_format($Location['land_area'], 2) ?> sqm</dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['built_area'])): ?>
                        <dt class="col-sm-4">Built Area:</dt>
                        <dd class="col-sm-8"><?= number_format($Location['built_area'], 2) ?> sqm</dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['year_built'])): ?>
                        <dt class="col-sm-4">Year Built:</dt>
                        <dd class="col-sm-8"><?= $Location['year_built'] ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['year_acquired'])): ?>
                        <dt class="col-sm-4">Year Acquired:</dt>
                        <dd class="col-sm-8"><?= $Location['year_acquired'] ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($Location['Location_value']) || !empty($Location['property_value'])): ?>
                        <dt class="col-sm-4">Location Value:</dt>
                        <dd class="col-sm-8"><?= format_currency($Location['Location_value'] ?? $Location['property_value'] ?? 0) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($manager)): ?>
                        <dt class="col-sm-4">Manager:</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars(trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? '')) ?: $manager['username']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <!-- Spaces Section -->
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-door-open"></i> Spaces (<?= count($Location['spaces'] ?? []) ?>)</h5>
                <a href="<?= base_url('spaces/create/' . $Location['id']) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Space
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($Location['spaces'])): ?>
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
                                <?php foreach ($Location['spaces'] as $space): ?>
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
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-bar-chart"></i> Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Total Spaces</h6>
                    <h4 class="mb-0"><?= count($Location['spaces'] ?? []) ?></h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Bookable Spaces</h6>
                    <h4 class="mb-0">
                        <?= count(array_filter($Location['spaces'] ?? [], function($s) { return $s['is_bookable'] == 1; })) ?>
                    </h4>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Leased Spaces</h6>
                    <h4 class="mb-0">
                        <?= count(array_filter($Location['spaces'] ?? [], function($s) { return $s['operational_mode'] === 'leased'; })) ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

