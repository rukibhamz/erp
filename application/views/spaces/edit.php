<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Edit Space</h1>
        <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-primary">
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

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="bi bi-pencil-square"></i> Edit Space Information</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('spaces/edit/' . $space['id']) ?>" method="POST" id="spaceForm" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="property_id" class="form-label">Property <span class="text-danger">*</span></label>
                    <select class="form-select" id="property_id" name="property_id" required>
                        <option value="">Select Property</option>
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?= $prop['id'] ?>" <?= $space['property_id'] == $prop['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prop['property_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="space_number" class="form-label">Space Number</label>
                    <input type="text" class="form-control" id="space_number" name="space_number" value="<?= htmlspecialchars($space['space_number'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="space_name" class="form-label">Space Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="space_name" name="space_name" value="<?= htmlspecialchars($space['space_name']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="event_space" <?= $space['category'] === 'event_space' ? 'selected' : '' ?>>Event Space</option>
                        <option value="commercial" <?= $space['category'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                        <option value="hospitality" <?= $space['category'] === 'hospitality' ? 'selected' : '' ?>>Hospitality</option>
                        <option value="storage" <?= $space['category'] === 'storage' ? 'selected' : '' ?>>Storage</option>
                        <option value="parking" <?= $space['category'] === 'parking' ? 'selected' : '' ?>>Parking</option>
                        <option value="residential" <?= $space['category'] === 'residential' ? 'selected' : '' ?>>Residential</option>
                        <option value="other" <?= $space['category'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="space_type" class="form-label">Space Type</label>
                    <input type="text" class="form-control" id="space_type" name="space_type" value="<?= htmlspecialchars($space['space_type'] ?? '') ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="floor" class="form-label">Floor/Level</label>
                    <input type="text" class="form-control" id="floor" name="floor" value="<?= htmlspecialchars($space['floor'] ?? '') ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="area" class="form-label">Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="area" name="area" value="<?= $space['area'] ?? '' ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?= $space['capacity'] ?? '' ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="operational_status" class="form-label">Operational Status</label>
                    <select class="form-select" id="operational_status" name="operational_status">
                        <option value="active" <?= $space['operational_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="under_maintenance" <?= $space['operational_status'] === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="under_renovation" <?= $space['operational_status'] === 'under_renovation' ? 'selected' : '' ?>>Under Renovation</option>
                        <option value="temporarily_closed" <?= $space['operational_status'] === 'temporarily_closed' ? 'selected' : '' ?>>Temporarily Closed</option>
                        <option value="decommissioned" <?= $space['operational_status'] === 'decommissioned' ? 'selected' : '' ?>>Decommissioned</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="operational_mode" class="form-label">Operational Mode</label>
                    <select class="form-select" id="operational_mode" name="operational_mode">
                        <option value="vacant" <?= $space['operational_mode'] === 'vacant' ? 'selected' : '' ?>>Vacant</option>
                        <option value="available_for_booking" <?= $space['operational_mode'] === 'available_for_booking' ? 'selected' : '' ?>>Available for Booking</option>
                        <option value="leased" <?= $space['operational_mode'] === 'leased' ? 'selected' : '' ?>>Leased</option>
                        <option value="owner_operated" <?= $space['operational_mode'] === 'owner_operated' ? 'selected' : '' ?>>Owner Operated</option>
                        <option value="reserved" <?= $space['operational_mode'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Options</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="is_bookable" name="is_bookable" value="1" <?= $space['is_bookable'] ? 'checked' : '' ?> onchange="toggleBookableConfig()">
                        <label class="form-check-label" for="is_bookable">
                            Make this space bookable (sync with Booking Module)
                        </label>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($space['description'] ?? '') ?></textarea>
                </div>

                <!-- Photo Management -->
                <div class="col-12">
                    <hr>
                    <h6 class="mb-3">Space Photos</h6>
                    
                    <div class="row g-3 mb-3">
                        <?php if (!empty($space['photos'])): ?>
                            <?php foreach ($space['photos'] as $photo): ?>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="card h-100 shadow-sm border-0">
                                        <div class="position-absolute top-0 end-0 p-1">
                                            <a href="<?= base_url('spaces/delete_photo/' . $photo['id']) ?>" 
                                               class="btn btn-sm btn-danger rounded-circle" 
                                               onclick="return confirm('Delete this photo?')">
                                                <i class="bi bi-x"></i>
                                            </a>
                                        </div>
                                        <img src="<?= base_url($photo['photo_url']) ?>" class="card-img-top rounded" style="height: 120px; object-fit: cover;">
                                        <?php if ($photo['is_primary']): ?>
                                            <div class="card-footer p-1 text-center bg-success text-white">
                                                <small>Primary</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted small">No photos uploaded yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="photos" class="form-label">Upload New Photos (Select multiple)</label>
                        <input type="file" class="form-control" id="photos" name="photos[]" multiple accept="image/*">
                        <div class="form-text">Supported formats: JPG, PNG, WEBP. Max size: 5MB per image.</div>
                    </div>
                </div>
                
                <!-- Bookable Configuration -->
                <div id="bookableConfig" style="display: <?= $space['is_bookable'] ? 'block' : 'none' ?>;">
                    <hr>
                    <h6 class="mb-3">Booking Configuration</h6>
                    
                    <?php
                    $pricingRules = $space['bookable_config'] ? json_decode($space['bookable_config']['pricing_rules'] ?? '{}', true) : [];
                    $bookingTypes = $space['bookable_config'] ? json_decode($space['bookable_config']['booking_types'] ?? '[]', true) : [];
                    $availabilityRules = $space['bookable_config'] ? json_decode($space['bookable_config']['availability_rules'] ?? '{}', true) : [];
                    $operatingHours = $availabilityRules['operating_hours'] ?? ['start' => '08:00', 'end' => '22:00'];
                    $daysAvailable = $availabilityRules['days_available'] ?? [0,1,2,3,4,5,6];
                    ?>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="hourly_rate" class="form-label">Hourly Rate</label>
                            <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate" value="<?= $pricingRules['base_hourly'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="daily_rate" class="form-label">Daily Rate</label>
                            <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate" value="<?= $pricingRules['base_daily'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="half_day_rate" class="form-label">Half-Day Rate</label>
                            <input type="number" step="0.01" class="form-control" id="half_day_rate" name="half_day_rate" value="<?= $pricingRules['half_day'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="weekly_rate" class="form-label">Weekly Rate</label>
                            <input type="number" step="0.01" class="form-control" id="weekly_rate" name="weekly_rate" value="<?= $pricingRules['weekly'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="minimum_duration" class="form-label">Minimum Duration (hours)</label>
                            <input type="number" class="form-control" id="minimum_duration" name="minimum_duration" value="<?= $space['bookable_config']['minimum_duration'] ?? 1 ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="maximum_duration" class="form-label">Maximum Duration (hours)</label>
                            <input type="number" class="form-control" id="maximum_duration" name="maximum_duration" value="<?= $space['bookable_config']['maximum_duration'] ?? '' ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="advance_booking_days" class="form-label">Advance Booking (days)</label>
                            <input type="number" class="form-control" id="advance_booking_days" name="advance_booking_days" value="<?= $space['bookable_config']['advance_booking_days'] ?? 365 ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="setup_time_buffer" class="form-label">Setup Time (minutes)</label>
                            <input type="number" class="form-control" id="setup_time_buffer" name="setup_time_buffer" value="<?= $space['bookable_config']['setup_time_buffer'] ?? 0 ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="cleanup_time_buffer" class="form-label">Cleanup Time (minutes)</label>
                            <input type="number" class="form-control" id="cleanup_time_buffer" name="cleanup_time_buffer" value="<?= $space['bookable_config']['cleanup_time_buffer'] ?? 0 ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="simultaneous_limit" class="form-label">Simultaneous Bookings</label>
                            <input type="number" class="form-control" id="simultaneous_limit" name="simultaneous_limit" value="<?= $space['bookable_config']['simultaneous_limit'] ?? 1 ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="operating_start" class="form-label">Operating Hours Start</label>
                            <input type="time" class="form-control" id="operating_start" name="operating_start" value="<?= $operatingHours['start'] ?? '08:00' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="operating_end" class="form-label">Operating Hours End</label>
                            <input type="time" class="form-control" id="operating_end" name="operating_end" value="<?= $operatingHours['end'] ?? '22:00' ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Booking Types</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_hourly" name="booking_types[]" value="hourly" <?= in_array('hourly', $bookingTypes) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bt_hourly">Hourly</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_half_day" name="booking_types[]" value="half_day" <?= in_array('half_day', $bookingTypes) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bt_half_day">Half-Day</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_full_day" name="booking_types[]" value="full_day" <?= in_array('full_day', $bookingTypes) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bt_full_day">Full-Day</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_multi_day" name="booking_types[]" value="multi_day" <?= in_array('multi_day', $bookingTypes) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bt_multi_day">Multi-Day</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Days Available</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_0" name="days_available[]" value="0" <?= in_array(0, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_0">Sunday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_1" name="days_available[]" value="1" <?= in_array(1, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_1">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_2" name="days_available[]" value="2" <?= in_array(2, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_2">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_3" name="days_available[]" value="3" <?= in_array(3, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_3">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_4" name="days_available[]" value="4" <?= in_array(4, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_4">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_5" name="days_available[]" value="5" <?= in_array(5, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_5">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="day_6" name="days_available[]" value="6" <?= in_array(6, $daysAvailable) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_6">Saturday</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('spaces/view/' . $space['id']) ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Update Space
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBookableConfig() {
    const checkbox = document.getElementById('is_bookable');
    const configDiv = document.getElementById('bookableConfig');
    configDiv.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

