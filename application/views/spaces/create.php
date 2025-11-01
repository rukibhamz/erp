<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Space</h1>
        <a href="<?= base_url('spaces' . ($property_id ? '?property_id=' . $property_id : '')) ?>" class="btn btn-outline-secondary">
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
        <a class="nav-link active" href="<?= base_url('spaces') ?>">
            <i class="bi bi-door-open"></i> Spaces
        </a>
        <a class="nav-link" href="<?= base_url('leases') ?>">
            <i class="bi bi-file-earmark-text"></i> Leases
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
        <form action="<?= base_url('spaces/create') ?>" method="POST" id="spaceForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="property_id" class="form-label">Property <span class="text-danger">*</span></label>
                    <select class="form-select" id="property_id" name="property_id" required>
                        <option value="">Select Property</option>
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?= $prop['id'] ?>" <?= $property_id == $prop['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prop['property_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="space_number" class="form-label">Space Number</label>
                    <input type="text" class="form-control" id="space_number" name="space_number">
                    <small class="text-muted">Leave blank to auto-generate</small>
                </div>
                
                <div class="col-md-6">
                    <label for="space_name" class="form-label">Space Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="space_name" name="space_name" required>
                </div>
                
                <div class="col-md-6">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="event_space">Event Space</option>
                        <option value="commercial">Commercial</option>
                        <option value="hospitality">Hospitality</option>
                        <option value="storage">Storage</option>
                        <option value="parking">Parking</option>
                        <option value="residential">Residential</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="space_type" class="form-label">Space Type</label>
                    <input type="text" class="form-control" id="space_type" name="space_type" placeholder="e.g., Hall, Store, Restaurant">
                </div>
                
                <div class="col-md-6">
                    <label for="floor" class="form-label">Floor/Level</label>
                    <input type="text" class="form-control" id="floor" name="floor">
                </div>
                
                <div class="col-md-4">
                    <label for="area" class="form-label">Area (sqm)</label>
                    <input type="number" step="0.01" class="form-control" id="area" name="area">
                </div>
                
                <div class="col-md-4">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" placeholder="Persons or vehicles">
                </div>
                
                <div class="col-md-4">
                    <label for="operational_status" class="form-label">Operational Status</label>
                    <select class="form-select" id="operational_status" name="operational_status">
                        <option value="active">Active</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="under_renovation">Under Renovation</option>
                        <option value="temporarily_closed">Temporarily Closed</option>
                        <option value="decommissioned">Decommissioned</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="operational_mode" class="form-label">Operational Mode</label>
                    <select class="form-select" id="operational_mode" name="operational_mode">
                        <option value="vacant">Vacant</option>
                        <option value="available_for_booking">Available for Booking</option>
                        <option value="leased">Leased</option>
                        <option value="owner_operated">Owner Operated</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Options</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="is_bookable" name="is_bookable" value="1" onchange="toggleBookableConfig()">
                        <label class="form-check-label" for="is_bookable">
                            Make this space bookable (sync with Booking Module)
                        </label>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <!-- Bookable Configuration (Hidden by default) -->
                <div id="bookableConfig" style="display: none;">
                    <hr>
                    <h6 class="mb-3">Booking Configuration</h6>
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="hourly_rate" class="form-label">Hourly Rate</label>
                            <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="daily_rate" class="form-label">Daily Rate</label>
                            <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="half_day_rate" class="form-label">Half-Day Rate</label>
                            <input type="number" step="0.01" class="form-control" id="half_day_rate" name="half_day_rate">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="weekly_rate" class="form-label">Weekly Rate</label>
                            <input type="number" step="0.01" class="form-control" id="weekly_rate" name="weekly_rate">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="minimum_duration" class="form-label">Minimum Duration (hours)</label>
                            <input type="number" class="form-control" id="minimum_duration" name="minimum_duration" value="1">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="maximum_duration" class="form-label">Maximum Duration (hours)</label>
                            <input type="number" class="form-control" id="maximum_duration" name="maximum_duration">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="advance_booking_days" class="form-label">Advance Booking (days)</label>
                            <input type="number" class="form-control" id="advance_booking_days" name="advance_booking_days" value="365">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="setup_time_buffer" class="form-label">Setup Time (minutes)</label>
                            <input type="number" class="form-control" id="setup_time_buffer" name="setup_time_buffer" value="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="cleanup_time_buffer" class="form-label">Cleanup Time (minutes)</label>
                            <input type="number" class="form-control" id="cleanup_time_buffer" name="cleanup_time_buffer" value="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="simultaneous_limit" class="form-label">Simultaneous Bookings</label>
                            <input type="number" class="form-control" id="simultaneous_limit" name="simultaneous_limit" value="1">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Booking Types</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_hourly" name="booking_types[]" value="hourly" checked>
                                <label class="form-check-label" for="bt_hourly">Hourly</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_half_day" name="booking_types[]" value="half_day">
                                <label class="form-check-label" for="bt_half_day">Half-Day</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_full_day" name="booking_types[]" value="full_day">
                                <label class="form-check-label" for="bt_full_day">Full-Day</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bt_multi_day" name="booking_types[]" value="multi_day">
                                <label class="form-check-label" for="bt_multi_day">Multi-Day</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('spaces') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Create Space
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

