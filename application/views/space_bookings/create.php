<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Space Booking</h1>
        <a href="<?= base_url('space-bookings') ?>" class="btn btn-primary">
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
        <a class="nav-link active" href="<?= base_url('space-bookings') ?>">
            <i class="bi bi-calendar-check"></i> Bookings
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

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="bi bi-calendar-plus"></i> Booking Information</h5>
    </div>
    <div class="card-body">
        <form action="<?= base_url('space-bookings/create') ?>" method="POST" id="bookingForm">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="space_id" class="form-label">Space <span class="text-danger">*</span></label>
                    <select class="form-select" id="space_id" name="space_id" required onchange="checkAvailability()">
                        <option value="">Select Space</option>
                        <?php foreach ($spaces as $space): ?>
                            <option value="<?= $space['id'] ?>" 
                                    <?= ($selected_space && $selected_space['id'] == $space['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($space['space_name']) ?> 
                                <?= $space['space_number'] ? '(' . htmlspecialchars($space['space_number']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="tenant_id" class="form-label">Tenant <span class="text-danger">*</span></label>
                    <select class="form-select" id="tenant_id" name="tenant_id" required>
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?= $tenant['id'] ?>">
                                <?= htmlspecialchars($tenant['business_name'] ?: $tenant['contact_person']) ?> 
                                (<?= htmlspecialchars($tenant['tenant_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="booking_date" class="form-label">Booking Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="booking_date" name="booking_date" 
                           required min="<?= date('Y-m-d') ?>" onchange="checkAvailability()">
                </div>
                
                <div class="col-md-4">
                    <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="start_time" name="start_time" 
                           required onchange="checkAvailability()">
                </div>
                
                <div class="col-md-4">
                    <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="end_time" name="end_time" 
                           required onchange="checkAvailability()">
                </div>
                
                <div class="col-md-6">
                    <label for="number_of_guests" class="form-label">Number of Guests</label>
                    <input type="number" class="form-control" id="number_of_guests" name="number_of_guests" 
                           min="0" value="0">
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info" id="availabilityAlert" style="display: none;">
                        <i class="bi bi-info-circle"></i> <span id="availabilityMessage"></span>
                    </div>
                </div>
                
                <div class="col-12">
                    <label for="booking_notes" class="form-label">Booking Notes</label>
                    <textarea class="form-control" id="booking_notes" name="booking_notes" rows="3"></textarea>
                </div>
                
                <div class="col-12">
                    <label for="special_requests" class="form-label">Special Requests</label>
                    <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= base_url('space-bookings') ?>" class="btn btn-primary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check-circle"></i> Create Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function checkAvailability() {
    const spaceId = document.getElementById('space_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const alertDiv = document.getElementById('availabilityAlert');
    const messageSpan = document.getElementById('availabilityMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!spaceId || !bookingDate || !startTime || !endTime) {
        alertDiv.style.display = 'none';
        return;
    }
    
    if (startTime >= endTime) {
        alertDiv.className = 'alert alert-warning';
        messageSpan.textContent = 'End time must be after start time.';
        alertDiv.style.display = 'block';
        submitBtn.disabled = true;
        return;
    }
    
    // Check availability via AJAX
    fetch('<?= base_url('space-bookings/check-availability') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `space_id=${spaceId}&booking_date=${bookingDate}&start_time=${startTime}&end_time=${endTime}&csrf_token=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.available) {
            alertDiv.className = 'alert alert-success';
            messageSpan.textContent = 'Time slot is available!';
            alertDiv.style.display = 'block';
            submitBtn.disabled = false;
        } else {
            alertDiv.className = 'alert alert-danger';
            messageSpan.textContent = 'Time slot is not available. Please select another time.';
            alertDiv.style.display = 'block';
            submitBtn.disabled = true;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertDiv.style.display = 'none';
        submitBtn.disabled = false;
    });
}
</script>

