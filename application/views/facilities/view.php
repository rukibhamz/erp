<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Facility: <?= htmlspecialchars($facility['facility_name'] ?? 'N/A') ?></h1>
        <div class="btn-group">
            <?php if (has_permission('bookings', 'update')): ?>
                <a href="<?= base_url('facilities/edit/' . $facility['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="<?= base_url('facilities') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<?php if (isset($flash) && $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Facility Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Facility Code</dt>
                    <dd><strong><?= htmlspecialchars($facility['facility_code'] ?? 'N/A') ?></strong></dd>
                    
                    <dt>Facility Name</dt>
                    <dd><?= htmlspecialchars($facility['facility_name'] ?? 'N/A') ?></dd>
                    
                    <?php if (!empty($facility['description'])): ?>
                    <dt>Description</dt>
                    <dd><?= htmlspecialchars($facility['description']) ?></dd>
                    <?php endif; ?>
                    
                    <dt>Capacity</dt>
                    <dd><?= number_format($facility['capacity'] ?? 0) ?> people</dd>
                    
                    <dt>Minimum Duration</dt>
                    <dd><?= number_format($facility['minimum_duration'] ?? 1) ?> hour(s)</dd>
                    
                    <dt>Setup Time</dt>
                    <dd><?= number_format($facility['setup_time'] ?? 0) ?> minutes</dd>
                    
                    <dt>Cleanup Time</dt>
                    <dd><?= number_format($facility['cleanup_time'] ?? 0) ?> minutes</dd>
                    
                    <dt>Status</dt>
                    <dd>
                        <span class="badge bg-<?= ($facility['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($facility['status'] ?? 'inactive') ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
        
        <?php if (!empty($facility['amenities']) || !empty($facility['features'])): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Amenities & Features</h5>
            </div>
            <div class="card-body">
                <?php 
                $amenities = !empty($facility['amenities']) ? json_decode($facility['amenities'], true) : [];
                $features = !empty($facility['features']) ? json_decode($facility['features'], true) : [];
                ?>
                <?php if (!empty($amenities)): ?>
                    <h6>Amenities</h6>
                    <ul>
                        <?php foreach ($amenities as $amenity): ?>
                            <li><?= htmlspecialchars($amenity) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($features)): ?>
                    <h6>Features</h6>
                    <ul>
                        <?php foreach ($features as $feature): ?>
                            <li><?= htmlspecialchars($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Pricing Information</h5>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Hourly Rate</dt>
                    <dd><?= format_currency($facility['hourly_rate'] ?? 0) ?></dd>
                    
                    <dt>Daily Rate</dt>
                    <dd><?= format_currency($facility['daily_rate'] ?? 0) ?></dd>
                    
                    <?php if (!empty($facility['weekend_rate']) && $facility['weekend_rate'] > 0): ?>
                    <dt>Weekend Rate</dt>
                    <dd><?= format_currency($facility['weekend_rate']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($facility['peak_rate']) && $facility['peak_rate'] > 0): ?>
                    <dt>Peak Rate</dt>
                    <dd><?= format_currency($facility['peak_rate']) ?></dd>
                    <?php endif; ?>
                    
                    <?php if (!empty($facility['security_deposit']) && $facility['security_deposit'] > 0): ?>
                    <dt>Security Deposit</dt>
                    <dd><?= format_currency($facility['security_deposit']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (has_permission('bookings', 'update')): ?>
                        <a href="<?= base_url('facilities/edit/' . $facility['id']) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Facility
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('bookings?facility_id=' . $facility['id']) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-calendar"></i> View Bookings
                    </a>
                    <?php if (has_permission('bookings', 'delete')): ?>
                        <a href="<?= base_url('facilities/delete/' . $facility['id']) ?>" class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this facility?')">
                            <i class="bi bi-trash"></i> Delete Facility
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

