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
                                <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="calculatePrice(); checkAvailability(); loadTimeSlots(); loadCalendar(); if (currentSpaceData && currentSpaceData.operating_hours) { generateTimeSlots(this.value); }">
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
                                <?php if ($from_spaces_module && $selected_space && $space_config): ?>
                                    <select name="start_time" id="start_time" class="form-select" required onchange="updateEndTimeOptions(); calculatePrice(); checkAvailability()">
                                        <option value="">Select Start Time</option>
                                    </select>
                                <?php else: ?>
                                    <input type="time" name="start_time" id="start_time" class="form-control" required onchange="calculatePrice(); checkAvailability()" step="900">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <?php if ($from_spaces_module && $selected_space && $space_config): ?>
                                    <select name="end_time" id="end_time" class="form-select" required onchange="calculatePrice(); checkAvailability()">
                                        <option value="">Select End Time</option>
                                    </select>
                                <?php else: ?>
                                    <input type="time" name="end_time" id="end_time" class="form-control" required onchange="calculatePrice(); checkAvailability()" step="900">
                                <?php endif; ?>
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
        option.dataset.operatingHours = JSON.stringify(space.operating_hours || {start: '08:00', end: '22:00'});
        option.dataset.daysAvailable = JSON.stringify(space.days_available || [0,1,2,3,4,5,6]);
        spaceSelect.appendChild(option);
    });
    
    spaceSelect.disabled = false;
    
    // If space is pre-selected, load its details
    <?php if ($selected_space): ?>
    const preSelectedSpaceId = '<?= $selected_space['id'] ?>';
    if (preSelectedSpaceId) {
        spaceSelect.value = preSelectedSpaceId;
        loadSpaceDetails();
    }
    <?php endif; ?>
}

function loadSpaceDetails() {
    const spaceSelect = document.getElementById('space_id');
    const selectedOption = spaceSelect.options[spaceSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        document.getElementById('spaceDetails').style.display = 'none';
        document.getElementById('booking_type').innerHTML = '<option value="">Select Space First</option>';
        document.getElementById('booking_type').disabled = true;
        currentSpaceData = null;
        // Clear time slots if using dropdowns
        const startTimeSelect = document.getElementById('start_time');
        const endTimeSelect = document.getElementById('end_time');
        if (startTimeSelect && startTimeSelect.tagName === 'SELECT') {
            startTimeSelect.innerHTML = '<option value="">Select Start Time</option>';
        }
        if (endTimeSelect && endTimeSelect.tagName === 'SELECT') {
            endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
        }
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
        maximum_duration: selectedOption.dataset.maximumDuration ? parseInt(selectedOption.dataset.maximumDuration) : null,
        operating_hours: JSON.parse(selectedOption.dataset.operatingHours || '{"start":"08:00","end":"22:00"}'),
        days_available: JSON.parse(selectedOption.dataset.daysAvailable || '[0,1,2,3,4,5,6]')
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
    
    // Generate time slots if using dropdowns and date is selected
    const startTimeSelect = document.getElementById('start_time');
    const bookingDate = document.getElementById('booking_date').value;
    if (startTimeSelect && startTimeSelect.tagName === 'SELECT' && bookingDate) {
        generateTimeSlots(bookingDate);
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

// Generate time slots based on operating hours
function generateTimeSlots(bookingDate) {
    if (!currentSpaceData || !currentSpaceData.operating_hours) {
        return;
    }
    
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    
    if (!startTimeSelect || startTimeSelect.tagName !== 'SELECT' || !endTimeSelect || endTimeSelect.tagName !== 'SELECT') {
        return; // Not using dropdowns
    }
    
    // Check if date is in days_available
    const date = new Date(bookingDate);
    const dayOfWeek = date.getDay(); // 0 = Sunday, 1 = Monday, etc.
    
    if (!currentSpaceData.days_available.includes(dayOfWeek)) {
        startTimeSelect.innerHTML = '<option value="">Not available on this day</option>';
        endTimeSelect.innerHTML = '<option value="">Not available on this day</option>';
        startTimeSelect.disabled = true;
        endTimeSelect.disabled = true;
        return;
    }
    
    startTimeSelect.disabled = false;
    endTimeSelect.disabled = false;
    
    const operatingStart = currentSpaceData.operating_hours.start || '08:00';
    const operatingEnd = currentSpaceData.operating_hours.end || '22:00';
    
    // Parse operating hours
    const [startHour, startMin] = operatingStart.split(':').map(Number);
    const [endHour, endMin] = operatingEnd.split(':').map(Number);
    
    const startMinutes = startHour * 60 + startMin;
    const endMinutes = endHour * 60 + endMin;
    
    // Generate time slots: primarily on the hour, with special cases for 30 and 45 minutes
    const timeSlots = [];
    for (let minutes = startMinutes; minutes < endMinutes; minutes += 60) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        timeSlots.push(timeStr);
    }
    
    // Add special 30 and 45 minute slots if they fall within operating hours
    // Add 30-minute slot after first hour if it's not already on the hour
    if (startMin !== 0 && startMin !== 30 && startMin !== 45) {
        const first30Min = startMinutes + (60 - startMin) + 30;
        if (first30Min < endMinutes) {
            const hours = Math.floor(first30Min / 60);
            const mins = first30Min % 60;
            const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
            if (!timeSlots.includes(timeStr)) {
                timeSlots.push(timeStr);
            }
        }
    }
    
    // Add 45-minute slot after first hour if it's not already on the hour or 30
    if (startMin !== 0 && startMin !== 30 && startMin !== 45) {
        const first45Min = startMinutes + (60 - startMin) + 45;
        if (first45Min < endMinutes) {
            const hours = Math.floor(first45Min / 60);
            const mins = first45Min % 60;
            const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
            if (!timeSlots.includes(timeStr)) {
                timeSlots.push(timeStr);
            }
        }
    }
    
    // Sort time slots
    timeSlots.sort();
    
    // Populate start time dropdown
    startTimeSelect.innerHTML = '<option value="">Select Start Time</option>';
    timeSlots.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        startTimeSelect.appendChild(option);
    });
    
    // Initially populate end time with same slots
    updateEndTimeOptions();
}

function updateEndTimeOptions() {
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const selectedStartTime = startTimeSelect.value;
    
    if (!selectedStartTime || !currentSpaceData || !currentSpaceData.operating_hours) {
        endTimeSelect.innerHTML = '<option value="">Select Start Time First</option>';
        return;
    }
    
    const operatingEnd = currentSpaceData.operating_hours.end || '22:00';
    const [endHour, endMin] = operatingEnd.split(':').map(Number);
    const endMinutes = endHour * 60 + endMin;
    
    // Parse selected start time
    const [startHour, startMin] = selectedStartTime.split(':').map(Number);
    const startMinutes = startHour * 60 + startMin;
    
    // Generate end time options (must be after start time)
    const endTimeSlots = [];
    for (let minutes = startMinutes + 60; minutes <= endMinutes; minutes += 60) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        endTimeSlots.push(timeStr);
    }
    
    // Add 30 and 45 minute options if they make sense
    const first30Min = startMinutes + 90; // 1 hour 30 minutes
    if (first30Min <= endMinutes) {
        const hours = Math.floor(first30Min / 60);
        const mins = first30Min % 60;
        const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        if (!endTimeSlots.includes(timeStr)) {
            endTimeSlots.push(timeStr);
        }
    }
    
    const first45Min = startMinutes + 105; // 1 hour 45 minutes
    if (first45Min <= endMinutes) {
        const hours = Math.floor(first45Min / 60);
        const mins = first45Min % 60;
        const timeStr = String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        if (!endTimeSlots.includes(timeStr)) {
            endTimeSlots.push(timeStr);
        }
    }
    
    // Sort end time slots
    endTimeSlots.sort();
    
    // Populate end time dropdown
    endTimeSelect.innerHTML = '<option value="">Select End Time</option>';
    endTimeSlots.forEach(time => {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        endTimeSelect.appendChild(option);
    });
}

// Initialize if location or space is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($selected_location): ?>
    // Load spaces for pre-selected location
    loadSpaces();
    <?php endif; ?>
    
    <?php if ($selected_space): ?>
    // Wait for spaces to load, then select the space
    setTimeout(function() {
        const spaceSelect = document.getElementById('space_id');
        if (spaceSelect) {
            spaceSelect.value = '<?= $selected_space['id'] ?>';
            loadSpaceDetails();
            
            <?php if (!empty($_GET['date'])): ?>
            document.getElementById('booking_date').value = '<?= $_GET['date'] ?>';
            const bookingDate = '<?= $_GET['date'] ?>';
            if (currentSpaceData && currentSpaceData.operating_hours) {
                generateTimeSlots(bookingDate);
            }
            loadTimeSlots();
            <?php endif; ?>
        }
    }, <?php echo $selected_location ? '300' : '100'; ?>);
    <?php endif; ?>
});
</script>

