<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Property</h1>
        <a href="<?= base_url('properties/view/' . $property['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Property Management Navigation -->
<div class="property-nav mb-4">
    <nav class="nav nav-pills nav-fill">
        <a class="nav-link" href="<?= base_url('properties') ?>">
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

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('properties/edit/' . $property['id']) ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="property_code" class="form-label">Property Code</label>
                    <input type="text" class="form-control" id="property_code" name="property_code" value="<?= htmlspecialchars($property['property_code']) ?>" readonly>
                </div>
                
                <div class="col-md-6">
                    <label for="property_name" class="form-label">Property Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="property_name" name="property_name" value="<?= htmlspecialchars($property['property_name']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="property_type" class="form-label">Property Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="property_type" name="property_type" required>
                        <option value="multi_purpose" <?= $property['property_type'] === 'multi_purpose' ? 'selected' : '' ?>>Multi-purpose Complex</option>
                        <option value="standalone_building" <?= $property['property_type'] === 'standalone_building' ? 'selected' : '' ?>>Standalone Building</option>
                        <option value="land" <?= $property['property_type'] === 'land' ? 'selected' : '' ?>>Land</option>
                        <option value="other" <?= $property['property_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="operational" <?= $property['status'] === 'operational' ? 'selected' : '' ?>>Operational</option>
                        <option value="under_construction" <?= $property['status'] === 'under_construction' ? 'selected' : '' ?>>Under Construction</option>
                        <option value="under_renovation" <?= $property['status'] === 'under_renovation' ? 'selected' : '' ?>>Under Renovation</option>
                        <option value="closed" <?= $property['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="ownership_status" class="form-label">Ownership Status</label>
                    <select class="form-select" id="ownership_status" name="ownership_status">
                        <option value="owned" <?= $property['ownership_status'] === 'owned' ? 'selected' : '' ?>>Owned</option>
                        <option value="leased" <?= $property['ownership_status'] === 'leased' ? 'selected' : '' ?>>Leased</option>
                        <option value="joint_venture" <?= $property['ownership_status'] === 'joint_venture' ? 'selected' : '' ?>>Joint Venture</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="manager_id" class="form-label">Property Manager</label>
                    <select class="form-select" id="manager_id" name="manager_id">
                        <option value="">Select Manager</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?= $manager['id'] ?>" <?= $property['manager_id'] == $manager['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? '')) ?: $manager['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($property['address'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($property['city'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state" value="<?= htmlspecialchars($property['state'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($property['country'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="postal_code" class="form-label">Postal Code</label>
                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= htmlspecialchars($property['postal_code'] ?? '') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="land_area" class="form-label">Land Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="land_area" name="land_area" value="<?= $property['land_area'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="built_area" class="form-label">Built Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="built_area" name="built_area" value="<?= $property['built_area'] ?? '' ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="year_built" class="form-label">Year Built</label>
                    <input type="number" class="form-control" id="year_built" name="year_built" value="<?= $property['year_built'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="year_acquired" class="form-label">Year Acquired</label>
                    <input type="number" class="form-control" id="year_acquired" name="year_acquired" value="<?= $property['year_acquired'] ?? '' ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="property_value" class="form-label">Property Value</label>
                    <input type="number" step="0.01" class="form-control" id="property_value" name="property_value" value="<?= $property['property_value'] ?? '' ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="gps_latitude" class="form-label">GPS Coordinates</label>
                    <div class="input-group">
                        <input type="number" step="0.00000001" class="form-control" id="gps_latitude" name="gps_latitude" value="<?= $property['gps_latitude'] ?? '' ?>" placeholder="Latitude">
                        <input type="number" step="0.00000001" class="form-control" id="gps_longitude" name="gps_longitude" value="<?= $property['gps_longitude'] ?? '' ?>" placeholder="Longitude">
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('properties/view/' . $property['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Property
                </button>
            </div>
        </form>
    </div>
</div>

