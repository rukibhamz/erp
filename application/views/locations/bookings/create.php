<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="page-title mb-0">Create Booking</h1>
        <a href="<?= base_url('locations/bookings') ?>" class="btn btn-primary">
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
        <a class="nav-link active" href="<?= base_url('locations/bookings') ?>">
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

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-calendar-plus"></i> Booking Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="bookingForm" action="<?= base_url('locations/create-booking') ?>">
                    <?php echo csrf_field(); ?>
                    
                    <!-- Location and Space Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select name="location_id" id="location_id" class="form-select" required onchange="loadSpaces()">
                                    <option value="">Select Location</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?= $location['id'] ?>" <?= ($selected_location && $selected_location['id'] == $location['id']) ? 'selected' : '' ?>>
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
                                    <?php if ($selected_location && !empty($spaces)): ?>
                                        <?php foreach ($spaces as $space): ?>
                                            <option value="<?= $space['id'] ?>" <?= ($selected_space && $selected_space['id'] == $space['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($space['space_name']) ?> 
                                                <?= $space['space_number'] ? '(' . htmlspecialchars($space['space_number']) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
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

                    <!-- Booking Type and Date -->
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
                                <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="calculatePrice(); checkAvailability(); loadTimeSlots(); loadCalendar()">
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

                    <!-- Time Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-control" required onchange="calculatePrice(); checkAvailability()" step="900">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-control" required onchange="calculatePrice(); checkAvailability()" step="900">
                            </div>
                        </div>
                    </div>

                    <!-- Availability Status -->
                    <div id="availabilityStatus" class="alert mb-4" style="display: none;">
                        <i class="bi"></i> <span id="availabilityMessage"></span>
                    </div>
                    
                    <!-- Time Slots Preview (if date and space selected) -->
                    <div id="timeSlotsPreview" class="mb-4" style="display: none;">
                        <h6>Available Time Slots</h6>
                        <div id="timeSlotsList" class="d-flex flex-wrap gap-2"></div>
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
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="customer_email" class="form-control">
                            </div>
                        </div>
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
                        <a href="<?= base_url('locations/bookings') ?>" class="btn btn-primary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Availability Calendar -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bi bi-calendar-month"></i> Availability Calendar</h5>
            </div>
            <div class="card-body">
                <div id="calendarContainer">
                    <p class="text-muted text-center">Select a space and date to view availability</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= base_url() ?>';
let currentSpaceData = null;
let spacesData = {};
let selectedDate = null;

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
        document.getElementById('calendarContainer').innerHTML = '<p class="text-muted text-center">Select a space and date to view availability</p>';
        return;
    }
    
    spaceSelect.innerHTML = '<option value="">Loading spaces...</option>';
    spaceSelect.disabled = true;
    
    if (spacesData[locationId]) {
        populateSpaces(spacesData[locationId]);
        return;
    }
    
    fetch(BASE_URL + 'locations/get-spaces-for-booking?location_id=' + locationId)
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
        option.dataset.bookingTypes = JSON.stringify(space.booking_types || []);
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
        currentSpaceData = null;
        return;
    }
    
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
    
    document.getElementById('spaceCapacity').textContent = currentSpaceData.capacity || 'N/A';
    document.getElementById('spaceBookingTypes').textContent = currentSpaceData.booking_types.map(t => t.charAt(0).toUpperCase() + t.slice(1)).join(', ') || 'N/A';
    document.getElementById('spaceDetails').style.display = 'block';
    
    const bookingTypeSelect = document.getElementById('booking_type');
    bookingTypeSelect.innerHTML = '<option value="">Select Booking Type</option>';
    
    const typeLabels = {
        'hourly': 'Hourly',
        'daily': 'Daily',
        'half_day': 'Half Day',
        'weekly': 'Weekly',
        'multi_day': 'Multi-Day'
    };
    
    if (currentSpaceData.booking_types && currentSpaceData.booking_types.length > 0) {
        currentSpaceData.booking_types.forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = typeLabels[type] || type.charAt(0).toUpperCase() + type.slice(1);
            bookingTypeSelect.appendChild(option);
        });
        bookingTypeSelect.disabled = false;
    } else {
        bookingTypeSelect.innerHTML = '<option value="hourly">Hourly</option><option value="daily">Daily</option>';
        bookingTypeSelect.disabled = false;
    }
    
    calculatePrice();
}

function loadCalendar() {
    const spaceId = document.getElementById('space_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const locationId = document.getElementById('location_id').value;
    
    if (!spaceId || !bookingDate) {
        document.getElementById('calendarContainer').innerHTML = '<p class="text-muted text-center"><small>Select a space and date to view availability</small></p>';
        return;
    }
    
    // Show link to full calendar view
    const calendarUrl = BASE_URL + 'locations/booking-calendar' + (locationId ? '/' + locationId : '') + '/' + spaceId;
    document.getElementById('calendarContainer').innerHTML = 
        '<div class="text-center">' +
        '<p class="text-muted mb-2"><small>Selected: ' + bookingDate + '</small></p>' +
        '<a href="' + calendarUrl + '" class="btn btn-sm btn-outline-primary" target="_blank">' +
        '<i class="bi bi-calendar-month"></i> View Full Calendar</a>' +
        '</div>';
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
    
    if (!bookingDate || !startTime || !endTime || !bookingType) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }
    
    const start = new Date(bookingDate + 'T' + startTime);
    const end = new Date(bookingDate + 'T' + endTime);
    const hours = (end - start) / (1000 * 60 * 60);
    
    if (hours <= 0) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }
    
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
    
    const deposit = currentSpaceData.security_deposit || 0;
    
    document.getElementById('estimatedPrice').textContent = '₦' + basePrice.toFixed(2);
    document.getElementById('securityDeposit').textContent = '₦' + deposit.toFixed(2);
    document.getElementById('pricePreview').style.display = 'block';
}

function checkAvailability() {
    const spaceId = document.getElementById('space_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (!spaceId || !bookingDate) {
        document.getElementById('availabilityStatus').style.display = 'none';
        return;
    }
    
    // If times are selected, check specific slot
    if (startTime && endTime) {
        const formData = new FormData();
        formData.append('space_id', spaceId);
        formData.append('booking_date', bookingDate);
        formData.append('start_time', startTime);
        formData.append('end_time', endTime);
        
        fetch(BASE_URL + 'locations/check-booking-availability', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('availabilityStatus');
            const messageSpan = document.getElementById('availabilityMessage');
            let icon = statusDiv.querySelector('i');
            if (!icon) {
                icon = document.createElement('i');
                statusDiv.insertBefore(icon, messageSpan);
            }
            
            if (data.available) {
                statusDiv.className = 'alert alert-success mb-4';
                icon.className = 'bi bi-check-circle-fill me-2';
                messageSpan.textContent = 'Time slot is available';
                statusDiv.style.display = 'block';
            } else {
                statusDiv.className = 'alert alert-danger mb-4';
                icon.className = 'bi bi-x-circle-fill me-2';
                messageSpan.textContent = 'Time slot is not available. Please choose another time.';
                statusDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

function loadTimeSlots() {
    const spaceId = document.getElementById('space_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    
    if (!spaceId || !bookingDate) {
        document.getElementById('timeSlotsPreview').style.display = 'none';
        return;
    }
    
    // Fetch available time slots for the selected date
    fetch(BASE_URL + 'booking-wizard/get-time-slots?space_id=' + spaceId + '&date=' + bookingDate)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.slots) {
                const container = document.getElementById('timeSlotsList');
                container.innerHTML = '';
                
                const availableSlots = data.slots.filter(slot => slot.available);
                if (availableSlots.length > 0) {
                    availableSlots.slice(0, 8).forEach(slot => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-outline-primary';
                        btn.textContent = slot.display || (slot.start + ' - ' + slot.end);
                        btn.onclick = function() {
                            document.getElementById('start_time').value = slot.start;
                            const endTime = new Date(bookingDate + 'T' + slot.end);
                            if (endTime < new Date(bookingDate + 'T' + slot.start)) {
                                endTime.setDate(endTime.getDate() + 1);
                            }
                            document.getElementById('end_time').value = endTime.toTimeString().slice(0, 5);
                            calculatePrice();
                            checkAvailability();
                        };
                        container.appendChild(btn);
                    });
                    document.getElementById('timeSlotsPreview').style.display = 'block';
                } else {
                    document.getElementById('timeSlotsPreview').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading time slots:', error);
        });
}

// Initialize if space is pre-selected
<?php if ($selected_space): ?>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($selected_location): ?>
    loadSpaces();
    <?php endif; ?>
    setTimeout(function() {
        document.getElementById('space_id').value = '<?= $selected_space['id'] ?>';
        loadSpaceDetails();
        <?php if (!empty($_GET['date'])): ?>
        document.getElementById('booking_date').value = '<?= $_GET['date'] ?>';
        loadTimeSlots();
        <?php endif; ?>
    }, 500);
});
<?php endif; ?>
</script>

