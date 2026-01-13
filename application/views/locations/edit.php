<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Location</h1>
        <a href="<?= base_url('locations/view/' . $Location['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
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

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="bi bi-pencil-square"></i> Edit Location Information</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('locations/edit/' . $Location['id']) ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="Location_code" class="form-label">Location Code</label>
                    <input type="text" class="form-control" id="Location_code" name="Location_code" value="<?= htmlspecialchars($Location['Location_code']) ?>" readonly>
                </div>
                
                <div class="col-md-6">
                    <label for="Location_name" class="form-label">Location Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="Location_name" name="Location_name" value="<?= htmlspecialchars($Location['Location_name']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="Location_type" class="form-label">Location Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="Location_type" name="Location_type" required>
                        <option value="multi_purpose" <?= $Location['Location_type'] === 'multi_purpose' ? 'selected' : '' ?>>Multi-purpose Complex</option>
                        <option value="standalone_building" <?= $Location['Location_type'] === 'standalone_building' ? 'selected' : '' ?>>Standalone Building</option>
                        <option value="land" <?= $Location['Location_type'] === 'land' ? 'selected' : '' ?>>Land</option>
                        <option value="other" <?= $Location['Location_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="operational" <?= $Location['status'] === 'operational' ? 'selected' : '' ?>>Operational</option>
                        <option value="under_construction" <?= $Location['status'] === 'under_construction' ? 'selected' : '' ?>>Under Construction</option>
                        <option value="under_renovation" <?= $Location['status'] === 'under_renovation' ? 'selected' : '' ?>>Under Renovation</option>
                        <option value="closed" <?= $Location['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="ownership_status" class="form-label">Ownership Status</label>
                    <select class="form-select" id="ownership_status" name="ownership_status">
                        <option value="owned" <?= $Location['ownership_status'] === 'owned' ? 'selected' : '' ?>>Owned</option>
                        <option value="leased" <?= $Location['ownership_status'] === 'leased' ? 'selected' : '' ?>>Leased</option>
                        <option value="joint_venture" <?= $Location['ownership_status'] === 'joint_venture' ? 'selected' : '' ?>>Joint Venture</option>
                    </select>
                </div>
                

                
                <div class="col-md-6">
                    <label for="manager_id" class="form-label">Location Manager</label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">Select Manager</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?= $manager['id'] ?>" <?= $Location['manager_id'] == $manager['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? '')) ?: $manager['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($Location['address'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($Location['city'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($Location['state'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($Location['country'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="postal_code" class="form-label">Postal Code</label>
                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= htmlspecialchars($Location['postal_code'] ?? '') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="land_area" class="form-label">Land Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="land_area" name="land_area" value="<?= $Location['land_area'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="built_area" class="form-label">Built Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="built_area" name="built_area" value="<?= $Location['built_area'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="year_built" class="form-label">Year Built</label>
                    <input type="number" class="form-control" id="year_built" name="year_built" value="<?= $Location['year_built'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="year_acquired" class="form-label">Year Acquired</label>
                    <input type="number" class="form-control" id="year_acquired" name="year_acquired" value="<?= $Location['year_acquired'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="Location_value" class="form-label">Location Value</label>
                    <input type="number" step="0.01" class="form-control" id="Location_value" name="Location_value" value="<?= $Location['Location_value'] ?? '' ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="gps_latitude" class="form-label">GPS Coordinates</label>
                    <div class="input-group">
                        <input type="number" step="0.00000001" class="form-control" id="gps_latitude" name="gps_latitude" value="<?= $Location['gps_latitude'] ?? '' ?>" placeholder="Latitude">
                        <input type="number" step="0.00000001" class="form-control" id="gps_longitude" name="gps_longitude" value="<?= $Location['gps_longitude'] ?? '' ?>" placeholder="Longitude">
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('locations/view/' . $Location['id']) ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Location
                </button>
            </div>
        </form>
    </div>
</div>

