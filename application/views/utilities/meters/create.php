<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Add Meter</h1>
        <a href="<?= base_url('utilities/meters') ?>" class="btn btn-primary">
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
        <form method="POST" action="<?= base_url('utilities/meters/create') ?>">
            <?php echo csrf_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="meter_number" class="form-label">Meter Number</label>
                    <input type="text" class="form-control" id="meter_number" name="meter_number" 
                           placeholder="Leave blank to auto-generate">
                    <small class="text-muted">Auto-generated if left blank</small>
                </div>
                <div class="col-md-6">
                    <label for="utility_type_id" class="form-label">Utility Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="utility_type_id" name="utility_type_id" required>
                        <option value="">Select Utility Type</option>
                        <?php foreach ($utility_types ?? [] as $type): ?>
                            <option value="<?= $type['id'] ?>">
                                <?= htmlspecialchars($type['utility_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="meter_type" class="form-label">Meter Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="meter_type" name="meter_type" required>
                        <option value="master">Master Meter</option>
                        <option value="sub">Sub Meter</option>
                        <option value="check">Check Meter</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="parent_meter_id" class="form-label">Parent Meter</label>
                    <select class="form-select" id="parent_meter_id" name="parent_meter_id">
                        <option value="">None (Master Meter)</option>
                        <?php foreach ($meters ?? [] as $meter): ?>
                            <option value="<?= $meter['id'] ?>">
                                <?= htmlspecialchars($meter['meter_number']) ?> - <?= htmlspecialchars($meter['utility_type_name'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Required for sub meters</small>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="property_id" class="form-label">Location</label>
                    <select class="form-select" id="property_id" name="property_id">
                        <option value="">Select Location</option>
                        <?php foreach ($properties ?? [] as $property): ?>
                            <option value="<?= $property['id'] ?>">
                                <?= htmlspecialchars($property['Location_name'] ?? $property['property_name'] ?? 'N/A') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="space_id" class="form-label">Space</label>
                    <select class="form-select" id="space_id" name="space_id">
                        <option value="">Select Space</option>
                        <?php foreach ($spaces ?? [] as $space): ?>
                            <option value="<?= $space['id'] ?>">
                                <?= htmlspecialchars($space['space_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="tenant_id" class="form-label">Tenant</label>
                    <select class="form-select" id="tenant_id" name="tenant_id">
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants ?? [] as $tenant): ?>
                            <option value="<?= $tenant['id'] ?>">
                                <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="meter_location" class="form-label">Meter Location</label>
                    <input type="text" class="form-control" id="meter_location" name="meter_location" 
                           placeholder="e.g., Basement, Room 101">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="installation_date" class="form-label">Installation Date</label>
                    <input type="date" class="form-control" id="installation_date" name="installation_date">
                </div>
                <div class="col-md-4">
                    <label for="meter_make" class="form-label">Make</label>
                    <input type="text" class="form-control" id="meter_make" name="meter_make">
                </div>
                <div class="col-md-4">
                    <label for="meter_model" class="form-label">Model</label>
                    <input type="text" class="form-control" id="meter_model" name="meter_model">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="meter_capacity" class="form-label">Capacity</label>
                    <input type="text" class="form-control" id="meter_capacity" name="meter_capacity" 
                           placeholder="e.g., 100A, 50kW">
                </div>
                <div class="col-md-4">
                    <label for="meter_rating" class="form-label">Rating</label>
                    <input type="text" class="form-control" id="meter_rating" name="meter_rating">
                </div>
                <div class="col-md-4">
                    <label for="reading_frequency" class="form-label">Reading Frequency</label>
                    <select class="form-select" id="reading_frequency" name="reading_frequency">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="initial_reading" class="form-label">Initial Reading</label>
                    <input type="number" step="0.01" class="form-control" id="initial_reading" 
                           name="initial_reading" value="0" min="0">
                </div>
                <div class="col-md-4">
                    <label for="last_calibration_date" class="form-label">Last Calibration Date</label>
                    <input type="date" class="form-control" id="last_calibration_date" name="last_calibration_date">
                </div>
                <div class="col-md-4">
                    <label for="next_calibration_due" class="form-label">Next Calibration Due</label>
                    <input type="date" class="form-control" id="next_calibration_due" name="next_calibration_due">
                </div>
            </div>

            <div class="mb-3">
                <label for="barcode" class="form-label">Barcode/Serial Number</label>
                <input type="text" class="form-control" id="barcode" name="barcode">
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('utilities/meters') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Meter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.getElementById('property_id');
    const spaceSelect = document.getElementById('space_id');
    
    // Load spaces when property is selected
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        if (propertyId) {
            // AJAX call to load spaces for this property
            fetch('<?= base_url('utilities/meters/get-spaces') ?>?property_id=' + propertyId)
                .then(response => response.json())
                .then(data => {
                    spaceSelect.innerHTML = '<option value="">Select Space</option>';
                    if (data.success && data.spaces) {
                        data.spaces.forEach(space => {
                            const option = document.createElement('option');
                            option.value = space.id;
                            option.textContent = space.space_name;
                            spaceSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading spaces:', error);
                });
        } else {
            spaceSelect.innerHTML = '<option value="">Select Space</option>';
        }
    });
});
</script>

