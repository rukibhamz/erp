<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Booking</h1>
        <a href="<?= base_url('bookings') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Booking Details</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="bookingForm">
                <?php echo csrf_field(); ?>
                
                <!-- Location and Space Selection -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select name="location_id" id="location_id" class="form-select" required onchange="loadSpaces()">
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location['id'] ?>">
                                        <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? 'N/A') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Space <span class="text-danger">*</span></label>
                            <select name="space_id" id="space_id" class="form-select" required onchange="loadSpaceDetails()" disabled>
                                <option value="">Select Location First</option>
                            </select>
                            <input type="hidden" name="facility_id" id="facility_id" value="">
                        </div>
                    </div>
                </div>

                <!-- Space Details (shown after space selection) -->
                <div id="spaceDetails" class="alert alert-info mb-4" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Capacity:</strong> <span id="spaceCapacity">-</span> guests
                        </div>
                        <div class="col-md-6">
                            <strong>Available Booking Types:</strong> <span id="spaceBookingTypes">-</span>
                        </div>
                    </div>
                </div>

                <!-- Booking Type Selection -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Booking Type <span class="text-danger">*</span></label>
                            <select name="booking_type" id="booking_type" class="form-select" required onchange="calculatePrice()" disabled>
                                <option value="">Select Space First</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" name="number_of_guests" id="number_of_guests" class="form-control" min="0" value="0" onchange="checkCapacity()">
                            <small class="text-muted" id="capacityWarning" style="display: none; color: red !important;">Exceeds space capacity!</small>
                        </div>
                    </div>
                </div>

                <!-- Price Preview -->
                <div class="alert alert-success" id="pricePreview" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Estimated Price:</strong> <span id="estimatedPrice">₦0.00</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Security Deposit:</strong> <span id="securityDeposit">₦0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <h5 class="mb-3 mt-4">Customer Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="customer_phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Discount Amount</label>
                            <input type="number" name="discount_amount" id="discount_amount" class="form-control" step="0.01" value="0" onchange="calculatePrice()">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Customer Address</label>
                    <textarea name="customer_address" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Booking Notes</label>
                    <textarea name="booking_notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Special Requests</label>
                    <textarea name="special_requests" class="form-control" rows="2"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('bookings') ?>" class="btn btn-primary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= base_url() ?>';
let currentSpaceData = null;
let spacesData = {}; // Cache spaces by location

// Load spaces when location is selected
function loadSpaces() {
    const locationId = document.getElementById('location_id').value;
    const spaceSelect = document.getElementById('space_id');
    const bookingTypeSelect = document.getElementById('booking_type');
    
    if (!locationId) {
        spaceSelect.innerHTML = '<option value="">Select Location First</option>';
        spaceSelect.disabled = true;
        bookingTypeSelect.innerHTML = '<option value="">Select Space First</option>';
        bookingTypeSelect.disabled = true;
        document.getElementById('spaceDetails').style.display = 'none';
        return;
    }
    
    // Show loading
    spaceSelect.innerHTML = '<option value="">Loading spaces...</option>';
    spaceSelect.disabled = true;
    
    // Check cache first
    if (spacesData[locationId]) {
        populateSpaces(spacesData[locationId]);
        return;
    }
    
    // Fetch from server
    fetch(BASE_URL + 'bookings/getSpacesForLocation?location_id=' + locationId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                spacesData[locationId] = data.spaces;
                populateSpaces(data.spaces);
            } else {
                spaceSelect.innerHTML = '<option value="">No spaces available</option>';
                alert('Error loading spaces: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            spaceSelect.innerHTML = '<option value="">Error loading spaces</option>';
            alert('Error loading spaces. Please try again.');
        });
}

function populateSpaces(spaces) {
    const spaceSelect = document.getElementById('space_id');
    
    if (!spaces || spaces.length === 0) {
        spaceSelect.innerHTML = '<option value="">No spaces available for this location</option>';
        spaceSelect.disabled = true;
        return;
    }
    
    spaceSelect.innerHTML = '<option value="">Select Space</option>';
    spaces.forEach(space => {
        const option = document.createElement('option');
        option.value = space.id;
        option.textContent = space.space_name + (space.space_number ? ' (' + space.space_number + ')' : '');
        option.dataset.facilityId = space.facility_id || '';
        option.dataset.bookingTypes = JSON.stringify(space.booking_types);
        option.dataset.hourlyRate = space.hourly_rate || 0;
        option.dataset.dailyRate = space.daily_rate || 0;
        option.dataset.halfDayRate = space.half_day_rate || 0;
        option.dataset.weeklyRate = space.weekly_rate || 0;
        option.dataset.securityDeposit = space.security_deposit || 0;
        option.dataset.capacity = space.capacity || 0;
        option.dataset.minimumDuration = space.minimum_duration || 1;
        option.dataset.maximumDuration = space.maximum_duration || '';
        spaceSelect.appendChild(option);
    });
    
    spaceSelect.disabled = false;
}

function loadSpaceDetails() {
    const spaceSelect = document.getElementById('space_id');
    const selectedOption = spaceSelect.options[spaceSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        document.getElementById('spaceDetails').style.display = 'none';
        document.getElementById('booking_type').innerHTML = '<option value="">Select Space First</option>';
        document.getElementById('booking_type').disabled = true;
        document.getElementById('facility_id').value = '';
        currentSpaceData = null;
        return;
    }
    
    // Store facility_id
    document.getElementById('facility_id').value = selectedOption.dataset.facilityId || '';
    
    // Store current space data
    currentSpaceData = {
        id: selectedOption.value,
        facility_id: selectedOption.dataset.facilityId || '',
        booking_types: JSON.parse(selectedOption.dataset.bookingTypes || '[]'),
        hourly_rate: parseFloat(selectedOption.dataset.hourlyRate || 0),
        daily_rate: parseFloat(selectedOption.dataset.dailyRate || 0),
        half_day_rate: parseFloat(selectedOption.dataset.halfDayRate || 0),
        weekly_rate: parseFloat(selectedOption.dataset.weeklyRate || 0),
        security_deposit: parseFloat(selectedOption.dataset.securityDeposit || 0),
        capacity: parseInt(selectedOption.dataset.capacity || 0),
        minimum_duration: parseInt(selectedOption.dataset.minimumDuration || 1),
        maximum_duration: selectedOption.dataset.maximumDuration ? parseInt(selectedOption.dataset.maximumDuration) : null
    };
    
    // Show space details
    document.getElementById('spaceCapacity').textContent = currentSpaceData.capacity || 'N/A';
    document.getElementById('spaceBookingTypes').textContent = currentSpaceData.booking_types.map(t => t.charAt(0).toUpperCase() + t.slice(1)).join(', ') || 'N/A';
    document.getElementById('spaceDetails').style.display = 'block';
    
    // Populate booking types
    const bookingTypeSelect = document.getElementById('booking_type');
    bookingTypeSelect.innerHTML = '<option value="">Select Booking Type</option>';
    
    const typeLabels = {
        'hourly': 'Hourly',
        'daily': 'Daily',
        'half_day': 'Half Day',
        'weekly': 'Weekly',
        'multi_day': 'Multi-Day'
    };
    
    currentSpaceData.booking_types.forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = typeLabels[type] || type.charAt(0).toUpperCase() + type.slice(1);
        bookingTypeSelect.appendChild(option);
    });
    
    bookingTypeSelect.disabled = false;
    
    // Calculate price if date/time are already set
    calculatePrice();
}

function checkCapacity() {
    const guests = parseInt(document.getElementById('number_of_guests').value) || 0;
    const warning = document.getElementById('capacityWarning');
    
    if (currentSpaceData && currentSpaceData.capacity > 0 && guests > currentSpaceData.capacity) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

function calculatePrice() {
    if (!currentSpaceData) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }
    
    const bookingDate = document.getElementById('booking_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const bookingType = document.getElementById('booking_type').value;
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    if (!bookingDate || !startTime || !endTime || !bookingType) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }
    
    // Calculate hours
    const start = new Date(bookingDate + 'T' + startTime);
    const end = new Date(bookingDate + 'T' + endTime);
    const hours = (end - start) / (1000 * 60 * 60);
    
    if (hours <= 0) {
        alert('End time must be after start time');
        return;
    }
    
    // Calculate base price based on booking type
    let basePrice = 0;
    const days = Math.ceil(hours / 24);
    
    switch(bookingType) {
        case 'hourly':
            basePrice = currentSpaceData.hourly_rate * hours;
            break;
        case 'daily':
            basePrice = currentSpaceData.daily_rate * days;
            break;
        case 'half_day':
            basePrice = currentSpaceData.half_day_rate * Math.ceil(days * 2);
            break;
        case 'weekly':
            basePrice = currentSpaceData.weekly_rate * Math.ceil(days / 7);
            break;
        case 'multi_day':
            basePrice = currentSpaceData.daily_rate * days;
            break;
        default:
            basePrice = currentSpaceData.hourly_rate * hours;
    }
    
    const total = basePrice - discount;
    const deposit = currentSpaceData.security_deposit || 0;
    
    document.getElementById('estimatedPrice').textContent = '₦' + total.toFixed(2);
    document.getElementById('securityDeposit').textContent = '₦' + deposit.toFixed(2);
    document.getElementById('pricePreview').style.display = 'block';
}

function checkAvailability() {
    // This would make an AJAX call to check availability
    // For now, it's handled server-side during form submission
}

// Form validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    if (!currentSpaceData) {
        e.preventDefault();
        alert('Please select a location and space.');
        return false;
    }
    
    const guests = parseInt(document.getElementById('number_of_guests').value) || 0;
    if (currentSpaceData.capacity > 0 && guests > currentSpaceData.capacity) {
        e.preventDefault();
        alert('Number of guests exceeds the space capacity of ' + currentSpaceData.capacity + '.');
        return false;
    }
    
    return true;
});
</script>
