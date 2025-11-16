<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Meter</h1>
        <a href="<?= base_url('utilities/meters') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<?php include(BASEPATH . 'views/utilities/_nav.php'); ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="<?= base_url('utilities/meters/create') ?>
            <?php echo csrf_field(); ?>" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="meter_number" class="form-label">Meter Number</label>
                    <input type="text" class="form-control" id="meter_number" name="meter_number">
                    <small class="text-muted">Leave blank to auto-generate</small>
                </div>
                
                <div class="col-md-6">
                    <label for="utility_type_id" class="form-label">Utility Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="utility_type_id" name="utility_type_id" required>
                        <option value="">Select Utility Type</option>
                        <?php foreach ($utility_types as $type): ?>
                            <option value="<?= $type['id'] ?>">
                                <?= htmlspecialchars($type['name']) ?> (<?= htmlspecialchars($type['unit_of_measure']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="meter_type" class="form-label">Meter Type</label>
                    <select class="form-select" id="meter_type" name="meter_type">
                        <option value="master">Master Meter</option>
                        <option value="sub_meter">Sub Meter</option>
                    </select>
                </div>
                
                <div class="col-md-6" id="parent_meter_div" style="display: none;">
                    <label for="parent_meter_id" class="form-label">Parent Meter</label>
                    <select class="form-select" id="parent_meter_id" name="parent_meter_id">
                        <option value="">Select Parent Meter</option>
                        <?php foreach ($meters as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['meter_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="property_id" class="form-label">Property</label>
                    <select class="form-select" id="property_id" name="property_id" onchange="loadSpaces(this.value)">
                        <option value="">Select Property</option>
                        <?php foreach ($properties as $property): ?>
                            <option value="<?= $property['id'] ?>">
                                <?= htmlspecialchars($property['property_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="space_id" class="form-label">Space</label>
                    <select class="form-select" id="space_id" name="space_id">
                        <option value="">Select Space (after selecting property)</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="tenant_id" class="form-label">Tenant</label>
                    <select class="form-select" id="tenant_id" name="tenant_id">
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= $tenant['id'] ?>">
                                <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="meter_location" class="form-label">Meter Location</label>
                    <input type="text" class="form-control" id="meter_location" name="meter_location" placeholder="e.g., Ground Floor, Main Entrance">
                </div>
                
                <div class="col-md-6">
                    <label for="installation_date" class="form-label">Installation Date</label>
                    <input type="date" class="form-control" id="installation_date" name="installation_date">
                </div>
                
                <div class="col-md-6">
                    <label for="meter_make" class="form-label">Meter Make</label>
                    <input type="text" class="form-control" id="meter_make" name="meter_make">
                </div>
                
                <div class="col-md-6">
                    <label for="meter_model" class="form-label">Meter Model</label>
                    <input type="text" class="form-control" id="meter_model" name="meter_model">
                </div>
                
                <div class="col-md-6">
                    <label for="meter_capacity" class="form-label">Meter Capacity</label>
                    <input type="text" class="form-control" id="meter_capacity" name="meter_capacity">
                </div>
                
                <div class="col-md-6">
                    <label for="meter_rating" class="form-label">Meter Rating</label>
                    <input type="text" class="form-control" id="meter_rating" name="meter_rating">
                </div>
                
                <div class="col-md-6">
                    <label for="initial_reading" class="form-label">Initial Reading</label>
                    <input type="number" step="0.0001" class="form-control" id="initial_reading" name="initial_reading" value="0">
                </div>
                
                <div class="col-md-6">
                    <label for="reading_frequency" class="form-label">Reading Frequency</label>
                    <select class="form-select" id="reading_frequency" name="reading_frequency">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="last_calibration_date" class="form-label">Last Calibration Date</label>
                    <input type="date" class="form-control" id="last_calibration_date" name="last_calibration_date">
                </div>
                
                <div class="col-md-6">
                    <label for="next_calibration_due" class="form-label">Next Calibration Due</label>
                    <input type="date" class="form-control" id="next_calibration_due" name="next_calibration_due">
                </div>
                
                <div class="col-md-6">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode" placeholder="For mobile scanning">
                </div>
                
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="faulty">Faulty</option>
                        <option value="maintenance">Under Maintenance</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('utilities/meters') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Meter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('meter_type').addEventListener('change', function() {
    document.getElementById('parent_meter_div').style.display = this.value === 'sub_meter' ? 'block' : 'none';
});

function loadSpaces(propertyId) {
    const spaceSelect = document.getElementById('space_id');
    spaceSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!propertyId) {
        spaceSelect.innerHTML = '<option value="">Select Space (after selecting property)</option>';
        return;
    }
    
    // Fetch spaces via AJAX or use a data attribute
    fetch('<?= base_url('spaces') ?>?property_id=' + propertyId)
        .then(response => response.text())
        .then(html => {
            // This is a simplified approach - in production, use a proper API endpoint
            spaceSelect.innerHTML = '<option value="">Select Space</option>';
        })
        .catch(err => {
            spaceSelect.innerHTML = '<option value="">Error loading spaces</option>';
        });
}
</script>

