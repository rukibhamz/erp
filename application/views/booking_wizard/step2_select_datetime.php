<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Progress Steps -->
            <div class="mb-5">
                <ul class="nav nav-pills nav-justified">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('booking-wizard') ?>"><strong>Step 1:</strong> Select Resource</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 3:</strong> Add Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 4:</strong> Your Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 5:</strong> Review & Pay</a>
                    </li>
                </ul>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                <?= htmlspecialchars($space['space_name'] ?? 'Space') ?>
                                <?php if ($location): ?>
                                    <small class="text-muted">at <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? '') ?></small>
                                <?php endif; ?>
                            </h4>
                            
                            <!-- Booking Type Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Booking Type <span class="text-danger">*</span></label>
                                <select id="booking_type" class="form-select form-select-lg" required>
                                    <option value="">Select Booking Type</option>
                                    <?php 
                                    $typeLabels = [
                                        'hourly' => 'Hourly',
                                        'daily' => 'Daily',
                                        'half_day' => 'Half Day',
                                        'weekly' => 'Weekly',
                                        'multi_day' => 'Multi-Day'
                                    ];
                                    foreach ($booking_types ?? [] as $type): ?>
                                        <option value="<?= htmlspecialchars($type) ?>">
                                            <?= $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Recurring Booking Option -->
                            <div class="mb-4" id="recurring-option" style="display: none;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_recurring">
                                    <label class="form-check-label" for="is_recurring">
                                        Make this a recurring booking (lease)
                                    </label>
                                </div>
                                <div id="recurring-details" style="display: none;" class="mt-3">
                                    <label class="form-label">Recurring Pattern</label>
                                    <select id="recurring_pattern" class="form-select">
                                        <option value="weekly">Weekly (Same day each week)</option>
                                        <option value="daily">Daily</option>
                                        <option value="monthly">Monthly (Same date each month)</option>
                                    </select>
                                    <label class="form-label mt-2">End Date (Optional)</label>
                                    <input type="date" id="recurring_end_date" class="form-control" 
                                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                    <small class="text-muted">Leave blank for ongoing lease</small>
                                </div>
                            </div>
                            
                            <!-- Date Pickers -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" id="booking_date" class="form-control form-control-lg" 
                                           min="<?= date('Y-m-d') ?>" 
                                           value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-6" id="end-date-container" style="display: none;">
                                    <label class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                                    <input type="date" id="booking_end_date" class="form-control form-control-lg" 
                                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                            </div>

                            <!-- Time Slot Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Time Slots</label>
                                <div class="mb-2">
                                    <span class="badge bg-success me-2">Available</span>
                                    <span class="badge bg-danger me-2">Occupied</span>
                                    <span class="badge bg-warning text-dark">Buffer (1 hour gap)</span>
                                </div>
                                <div id="time-slots-container" class="row g-2">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i> Please select a date and booking type to see available time slots
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Time Summary -->
                            <div id="selected-time-summary" class="alert alert-success" style="display: none;">
                                <h6><i class="bi bi-check-circle"></i> Selected Booking Details</h6>
                                <p class="mb-0">
                                    <strong>Type:</strong> <span id="selected-type"></span><br>
                                    <strong>Start Date:</strong> <span id="selected-date"></span><br>
                                    <span id="selected-end-date-container" style="display: none;">
                                        <strong>End Date:</strong> <span id="selected-end-date"></span><br>
                                    </span>
                                    <strong>Time:</strong> <span id="selected-time"></span><br>
                                    <span id="selected-recurring-container" style="display: none;">
                                        <strong>Recurring:</strong> <span id="selected-recurring"></span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Resource Info Sidebar -->
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h5 class="card-title">Space Details</h5>
                            
                            <?php if (!empty($photos)): ?>
                                <img src="<?= base_url($photos[0]['photo_path'] ?? '') ?>" class="img-fluid rounded mb-3" alt="Space Image">
                            <?php endif; ?>
                            
                            <?php if ($location): ?>
                                <p class="text-muted small mb-2">
                                    <strong>Location:</strong> <?= htmlspecialchars($location['Location_name'] ?? $location['property_name'] ?? '') ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="text-muted small"><?= htmlspecialchars($space['description'] ?? '') ?></p>
                            
                            <div class="mb-3">
                                <strong>Capacity:</strong> <?= $space['capacity'] ?? 'N/A' ?> people<br>
                                <?php if (!empty($booking_types)): ?>
                                    <strong>Available Types:</strong> <?= implode(', ', array_map(function($t) { return ucfirst(str_replace('_', ' ', $t)); }, $booking_types)) ?><br>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($amenities)): ?>
                                <div class="mb-3">
                                    <strong>Amenities:</strong><br>
                                    <?php foreach ($amenities as $amenity): ?>
                                        <span class="badge bg-light text-dark mb-1"><?= htmlspecialchars($amenity) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <strong>Pricing:</strong><br>
                                <span class="h5 text-primary"><?= format_currency($space['hourly_rate'] ?? 0) ?></span>
                                <small class="text-muted">/hour</small>
                                <?php if (!empty($space['daily_rate']) && $space['daily_rate'] > 0): ?>
                                    <br><small>or <?= format_currency($space['daily_rate']) ?>/day</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <a href="<?= base_url('booking-wizard') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="button" id="continue-btn" class="btn btn-primary float-end" disabled>
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingDate = document.getElementById('booking_date');
    const timeSlotsContainer = document.getElementById('time-slots-container');
    const continueBtn = document.getElementById('continue-btn');
    const selectedDateSpan = document.getElementById('selected-date');
    const selectedTimeSpan = document.getElementById('selected-time');
    const selectedTimeSummary = document.getElementById('selected-time-summary');
    
    const spaceId = <?= $space['id'] ?? 0 ?>;
    let selectedDate = '';
    let selectedEndDate = '';
    let selectedStartTime = '';
    let selectedEndTime = '';
    let selectedBookingType = '';
    let isRecurring = false;
    let recurringPattern = '';
    let recurringEndDate = '';

    const bookingTypeSelect = document.getElementById('booking_type');
    const endDateContainer = document.getElementById('end-date-container');
    const bookingEndDate = document.getElementById('booking_end_date');
    const recurringOption = document.getElementById('recurring-option');
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurringDetails = document.getElementById('recurring-details');
    const recurringPatternSelect = document.getElementById('recurring_pattern');
    const recurringEndDateInput = document.getElementById('recurring_end_date');

    // Show/hide end date based on booking type
    bookingTypeSelect.addEventListener('change', function() {
        selectedBookingType = this.value;
        const isMultiDay = this.value === 'multi_day' || this.value === 'weekly';
        endDateContainer.style.display = isMultiDay ? 'block' : 'none';
        recurringOption.style.display = (this.value === 'hourly' || this.value === 'daily' || this.value === 'multi_day') ? 'block' : 'none';
        
        if (selectedDate) {
            loadTimeSlots(spaceId, selectedDate, selectedEndDate || selectedDate);
        }
    });

    // Handle recurring booking checkbox
    isRecurringCheckbox.addEventListener('change', function() {
        isRecurring = this.checked;
        recurringDetails.style.display = isRecurring ? 'block' : 'none';
        if (!isRecurring) {
            recurringPattern = '';
            recurringEndDate = '';
        }
    });

    recurringPatternSelect.addEventListener('change', function() {
        recurringPattern = this.value;
    });

    recurringEndDateInput.addEventListener('change', function() {
        recurringEndDate = this.value;
    });

    // Load time slots when date changes
    bookingDate.addEventListener('change', function() {
        const date = this.value;
        if (!date) return;
        
        selectedDate = date;
        if (bookingEndDate.value) {
            selectedEndDate = bookingEndDate.value;
        }
        
        if (selectedBookingType) {
            loadTimeSlots(spaceId, date, selectedEndDate || date);
        }
        
        // Update end date minimum
        if (bookingEndDate) {
            bookingEndDate.min = date;
            if (!bookingEndDate.value || bookingEndDate.value < date) {
                bookingEndDate.value = date;
                selectedEndDate = date;
            }
        }
    });

    bookingEndDate.addEventListener('change', function() {
        selectedEndDate = this.value;
        if (selectedBookingType && selectedDate) {
            loadTimeSlots(spaceId, selectedDate, selectedEndDate || selectedDate);
        }
    });

    // Load initial slots if date and booking type are selected
    if (bookingDate.value && bookingTypeSelect.value) {
        selectedBookingType = bookingTypeSelect.value;
        loadTimeSlots(spaceId, bookingDate.value);
    }

    function loadTimeSlots(spaceId, date, endDate = null) {
        if (!selectedBookingType) {
            timeSlotsContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning">Please select a booking type first.</div></div>';
            return;
        }
        
        const checkEndDate = endDate || date;
        timeSlotsContainer.innerHTML = '<div class="col-12"><div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div></div>';
        
        fetch(`<?= base_url('booking-wizard/get-time-slots') ?>?space_id=${spaceId}&date=${date}&end_date=${checkEndDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    
                    // Show available slots
                    if (data.slots && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            html += `
                                <div class="col-md-6 col-lg-4">
                                    <button type="button" class="btn btn-success w-100 time-slot-btn available-slot" 
                                            data-start="${slot.start}" 
                                            data-end="${slot.end}"
                                            data-date="${slot.date}"
                                            style="min-height: 60px;">
                                        <small class="d-block">${slot.date === date ? 'Today' : new Date(slot.date).toLocaleDateString()}</small>
                                        ${slot.display}
                                    </button>
                                </div>
                            `;
                        });
                    }
                    
                    // Show occupied slots (for reference)
                    if (data.occupied && data.occupied.length > 0) {
                        data.occupied.forEach(slot => {
                            html += `
                                <div class="col-md-6 col-lg-4">
                                    <button type="button" class="btn btn-danger w-100 time-slot-btn occupied-slot" 
                                            disabled
                                            style="min-height: 60px; opacity: 0.7; cursor: not-allowed;"
                                            title="Occupied by: ${slot.occupied_by || 'Another booking'}">
                                        <small class="d-block">${slot.date === date ? 'Today' : new Date(slot.date).toLocaleDateString()}</small>
                                        ${slot.display}
                                        <br><small><i class="bi bi-lock"></i> Occupied</small>
                                    </button>
                                </div>
                            `;
                        });
                    }
                    
                    if (html === '') {
                        html = '<div class="col-12"><div class="alert alert-warning">No time slots available for the selected date range. Please select another date.</div></div>';
                    }
                    
                    timeSlotsContainer.innerHTML = html;
                    
                    // Add click handlers for available slots only
                    document.querySelectorAll('.available-slot').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // Remove active class from all buttons
                            document.querySelectorAll('.time-slot-btn').forEach(b => {
                                b.classList.remove('active');
                            });
                            
                            // Add active class to selected
                            this.classList.add('active');
                            
                            selectedStartTime = this.dataset.start;
                            selectedEndTime = this.dataset.end;
                            const slotDate = this.dataset.date || selectedDate;
                            
                            // Update summary
                            document.getElementById('selected-type').textContent = bookingTypeSelect.options[bookingTypeSelect.selectedIndex].text;
                            selectedDateSpan.textContent = new Date(slotDate).toLocaleDateString();
                            selectedTimeSpan.textContent = `${selectedStartTime} - ${selectedEndTime}`;
                            
                            if (selectedEndDate && selectedEndDate !== selectedDate) {
                                document.getElementById('selected-end-date').textContent = new Date(selectedEndDate).toLocaleDateString();
                                document.getElementById('selected-end-date-container').style.display = 'block';
                            } else {
                                document.getElementById('selected-end-date-container').style.display = 'none';
                            }
                            
                            if (isRecurring && recurringPattern) {
                                document.getElementById('selected-recurring').textContent = recurringPatternSelect.options[recurringPatternSelect.selectedIndex].text + 
                                    (recurringEndDate ? ' until ' + new Date(recurringEndDate).toLocaleDateString() : ' (ongoing)');
                                document.getElementById('selected-recurring-container').style.display = 'block';
                            } else {
                                document.getElementById('selected-recurring-container').style.display = 'none';
                            }
                            
                            selectedTimeSummary.style.display = 'block';
                            
                            // Enable continue button
                            continueBtn.disabled = false;
                        });
                    });
                } else {
                    timeSlotsContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning">No available time slots for this date. Please select another date.</div></div>';
                    selectedTimeSummary.style.display = 'none';
                    continueBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                timeSlotsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading time slots. Please try again.</div></div>';
            });
    }

    // Continue button handler
    continueBtn.addEventListener('click', function() {
        const bookingType = bookingTypeSelect.value;
        if (!bookingType) {
            alert('Please select a booking type');
            return;
        }
        if (!selectedDate || !selectedStartTime || !selectedEndTime) {
            alert('Please select a date and time slot');
            return;
        }
        
        // Validate end date for multi-day bookings
        if ((bookingType === 'multi_day' || bookingType === 'weekly') && (!selectedEndDate || selectedEndDate < selectedDate)) {
            alert('Please select a valid end date');
            return;
        }
        
        // Validate recurring booking
        if (isRecurring && !recurringPattern) {
            alert('Please select a recurring pattern');
            return;
        }

        // Build request body
        const requestData = {
            step: 2,
            data: {
                space_id: spaceId,
                location_id: <?= $space['property_id'] ?? 0 ?>,
                booking_type: bookingType,
                date: selectedDate,
                end_date: selectedEndDate || selectedDate,
                start_time: selectedStartTime,
                end_time: selectedEndTime,
                is_recurring: isRecurring ? 1 : 0,
                recurring_pattern: recurringPattern || '',
                recurring_end_date: recurringEndDate || ''
            }
        };
        
        // Save to session via AJAX
        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: Object.keys(requestData.data).map(key => 
                `data[${key}]=${encodeURIComponent(requestData.data[key])}`
            ).join('&') + `&step=${requestData.step}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `<?= base_url('booking-wizard/step3/') ?>${spaceId}`;
            } else {
                alert('Error saving data. Please try again.');
            }
        });
    });
});
</script>

<style>
.nav-pills .nav-link {
    background-color: #f8f9fa;
    color: #000;
    border: 1px solid #dee2e6;
}
.nav-pills .nav-link.active {
    background-color: #000;
    color: #fff;
    border-color: #000;
}
.time-slot-btn {
    transition: all 0.2s;
}
.time-slot-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

