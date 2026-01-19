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
                        <a class="nav-link" href="<?= base_url('booking-wizard') ?>"><strong>Step 1:</strong> Location & Space</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><strong>Step 2:</strong> Date & Time</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 3:</strong> Extras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 4:</strong> Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link disabled" href="#"><strong>Step 5:</strong> Review & Pay</a>
                    </li>
                </ul>
            </div>

            <div class="text-center mb-4">
                <h1 class="display-6 fw-bold mb-3">Select Date & Time</h1>
                <p class="lead text-muted">Choose your booking type, date and time duration</p>
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
                            
                            <!-- Duration Selection (Hidden by default) -->
                            <div class="mb-4" id="duration-container" style="display: none;">
                                <label class="form-label fw-bold">Duration</label>
                                <select id="duration" class="form-select form-select-lg">
                                    <!-- Options populated by JS -->
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
                                           value="<?= date('Y-m-d') ?>" disabled>
                                    <small class="text-muted d-block mt-1">Select booking type first</small>
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
                                <?php if (count($photos) > 1): ?>
                                    <div id="spacePhotoCarousel" class="carousel slide mb-3 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                                        <div class="carousel-inner">
                                            <?php foreach ($photos as $index => $photo): ?>
                                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                    <img src="<?= base_url($photo['photo_url']) ?>" class="d-block w-100" style="height: 250px; object-fit: cover;" alt="Space Photo">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#spacePhotoCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#spacePhotoCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= base_url($photos[0]['photo_url'] ?? '') ?>" class="img-fluid rounded mb-3 shadow-sm" style="height: 250px; width: 100%; object-fit: cover;" alt="Space Image">
                                <?php endif; ?>
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
    const timeSlotsContainer = document.getElementById('time-slots-container');
    const continueBtn = document.getElementById('continue-btn');
    const selectedDateSpan = document.getElementById('selected-date');
    const selectedTimeSpan = document.getElementById('selected-time');
    const selectedTimeSummary = document.getElementById('selected-time-summary');
    
    const spaceId = <?= $space['id'] ?? 0 ?>;
    const resourceId = <?= $space['facility_id'] ?? $space['id'] ?>;
    let selectedDate = '';
    let selectedEndDate = '';
    let selectedStartTime = '';
    let selectedEndTime = '';
    let selectedBookingType = '';
    let selectedDuration = 1; // Hours
    let isRecurring = false;
    let recurringPattern = '';
    let recurringEndDate = '';
    
    // Cache for slots data
    let currentSlotsData = [];

    const bookingTypeSelect = document.getElementById('booking_type');
    const durationContainer = document.getElementById('duration-container');
    const durationSelect = document.getElementById('duration');
    const bookingDate = document.getElementById('booking_date');
    const endDateContainer = document.getElementById('end-date-container');
    const bookingEndDate = document.getElementById('booking_end_date');
    const recurringOption = document.getElementById('recurring-option');
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurringDetails = document.getElementById('recurring-details');
    const recurringPatternSelect = document.getElementById('recurring_pattern');
    const recurringEndDateInput = document.getElementById('recurring_end_date');
    
    // Initially disable date input until booking type is selected
    if (bookingDate) {
        bookingDate.disabled = true;
    }

    // Show/hide end date and duration based on booking type
    bookingTypeSelect.addEventListener('change', function() {
        selectedBookingType = this.value;
        const isMultiDay = this.value === 'multi_day' || this.value === 'weekly';
        
        endDateContainer.style.display = isMultiDay ? 'block' : 'none';
        recurringOption.style.display = (this.value === 'hourly' || this.value === 'daily' || this.value === 'multi_day') ? 'block' : 'none';
        
        // Handle Duration Logic
        updateDurationOptions(selectedBookingType);
        
        // Enable date input
        if (selectedBookingType) {
            bookingDate.removeAttribute('disabled');
        } else {
            bookingDate.setAttribute('disabled', 'disabled');
            timeSlotsContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning">Please select a booking type first.</div></div>';
        }
        
        if (selectedBookingType && selectedDate) {
            loadTimeSlots(spaceId, selectedDate, selectedEndDate || selectedDate);
        }
    });

    durationSelect.addEventListener('change', function() {
        selectedDuration = parseInt(this.value);
        if (selectedBookingType && selectedDate && currentSlotsData.length > 0) {
            renderTimeSlots(currentSlotsData);
        }
    });

    function updateDurationOptions(type) {
        let options = '';
        if (type === 'hourly') {
            durationContainer.style.display = 'block';
            options += '<option value="1">1 Hour</option>';
            options += '<option value="2">2 Hours</option>';
            options += '<option value="3">3 Hours</option>';
            options += '<option value="4">4 Hours</option>';
            options += '<option value="5">5 Hours</option>';
            options += '<option value="6">6 Hours</option>';
            options += '<option value="8">8 Hours</option>';
        } else if (type === 'daily') {
            durationContainer.style.display = 'block';
            options += '<option value="4">4 Hours</option>';
            options += '<option value="6">6 Hours</option>';
            options += '<option value="8">8 Hours</option>';
            options += '<option value="12">12 Hours</option>';
            options += '<option value="24">Full Day (24 Hours)</option>';
        } else {
            durationContainer.style.display = 'none';
            selectedDuration = 0; // Not applicable or calculated differently
        }
        durationSelect.innerHTML = options;
        
        // Set default
        if (type === 'hourly') selectedDuration = 1;
        if (type === 'daily') selectedDuration = 8; // Default valid reasonable time
        durationSelect.value = selectedDuration;
    }

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
        selectedBookingType = bookingTypeSelect.value; // Initialize
        updateDurationOptions(selectedBookingType);
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
                    currentSlotsData = data.slots || [];
                    
                    // Also store occupied for reference if needed, but render logic mainly cares about availablity
                    // For contiguous checking we need to know all slots in order. 
                    // Current API returns valid slots and occupied slots separately. This makes "gap" checking hard.
                    // Ideally we should merge them or assume they are derived from a full day grid.
                    // Facility_model generates 'allSlots' then filters.
                    
                    renderTimeSlots(currentSlotsData);
                } else {
                     timeSlotsContainer.innerHTML = `<div class="col-12"><div class="alert alert-warning">${data.message || 'No available time slots.'}</div></div>`;
                     selectedTimeSummary.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                timeSlotsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading time slots. Please try again.</div></div>';
            });
    }

    function renderTimeSlots(slots) {
        let html = '';
        let availableCount = 0;
        
        // Helper to parsing HH:mm
        const parseTime = (t) => {
            const [h, m] = t.split(':').map(Number);
            return h * 60 + m;
        };
        
        const formatTime = (minutes) => {
            const h = Math.floor(minutes / 60);
            const m = minutes % 60;
            return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`;
        };

        const formatDisplayTime = (minutes) => {
            let h = Math.floor(minutes / 60);
            const m = minutes % 60;
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12;
            return `${h}:${String(m).padStart(2,'0')} ${ampm}`;
        };

        // Allow selection of slots where (slot_start) -> (slot_start + duration) is fully available
        // Since `slots` only contains AVAILABLE slots (without gaps?), we need to be careful.
        // We really need to know if the consecutive slots exist in `slots`.
        
        // Sort slots by start time to be sure
        slots.sort((a, b) => parseTime(a.start) - parseTime(b.start));
        
        slots.forEach((slot, index) => {
            // Check if we can satisfy the duration starting from this slot
            const startMin = parseTime(slot.start);
            const targetEndMin = startMin + (selectedDuration * 60);
            
            // Check availability for full duration
            // We need to find if all 60-min blocks between startMin and targetEndMin exist in 'slots'
            let isFeasible = true;
            
            if (selectedDuration > 1) {
                for (let i = 1; i < selectedDuration; i++) {
                    const requiredStart = startMin + (i * 60); // Start of next hour
                    // Find a slot that starts at requiredStart
                    // We assume slots are atomic 1-hour slots from backend
                    const foundNext = slots.find(s => parseTime(s.start) === requiredStart && s.date === slot.date);
                    if (!foundNext) {
                        isFeasible = false;
                        break;
                    }
                }
            }
            
            if (isFeasible && targetEndMin <= 24 * 60) { // must end within same day logic? Backend seems to split days
                availableCount++;
                const endDisplay = formatDisplayTime(targetEndMin);
                const endDbStr = formatTime(targetEndMin);
                
                html += `
                    <div class="col-md-6 col-lg-4">
                        <button type="button" class="btn btn-outline-success w-100 time-slot-btn available-slot" 
                                data-start="${slot.start}" 
                                data-end="${endDbStr}"
                                data-date="${slot.date}"
                                style="min-height: 60px;">
                            <small class="d-block text-muted">${slot.date === selectedDate ? 'Today' : new Date(slot.date).toLocaleDateString()}</small>
                            <span class="fw-bold">${slot.display.split('-')[0]} - ${endDisplay}</span>
                            <div class="small text-success">${selectedDuration} Hour${selectedDuration > 1 ? 's' : ''}</div>
                        </button>
                    </div>
                `;
            }
        });
        
        if (availableCount === 0) {
             html = `<div class="col-12"><div class="alert alert-warning">
                No consecutive time slots available for ${selectedDuration} hours on this date. 
                Try checking individual hours or a different date.
             </div></div>`;
        }
        
        timeSlotsContainer.innerHTML = html;
        
        // Add click handlers
         document.querySelectorAll('.available-slot').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all
                document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('active', 'btn-success'));
                document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.add('btn-outline-success'));
                
                // Add active
                this.classList.remove('btn-outline-success');
                this.classList.add('btn-success', 'active');
                
                selectedStartTime = this.dataset.start;
                selectedEndTime = this.dataset.end;
                const slotDate = this.dataset.date || selectedDate;
                
                // Update summary
                document.getElementById('selected-type').textContent = bookingTypeSelect.options[bookingTypeSelect.selectedIndex].text;
                selectedDateSpan.textContent = new Date(slotDate).toLocaleDateString();
                selectedTimeSpan.textContent = `${formatDisplayTime(parseTime(selectedStartTime))} - ${formatDisplayTime(parseTime(selectedEndTime))}`;
                
                if (selectedEndDate && selectedEndDate !== selectedDate) {
                    document.getElementById('selected-end-date').textContent = new Date(selectedEndDate).toLocaleDateString();
                    document.getElementById('selected-end-date-container').style.display = 'block';
                } else {
                    document.getElementById('selected-end-date-container').style.display = 'none';
                }
                
                selectedTimeSummary.style.display = 'block';
                continueBtn.disabled = false;
            });
        });
    }

    // Continue button handler
    continueBtn.addEventListener('click', function() {
        const bookingType = bookingTypeSelect.value;
        if (!bookingType) {
            alert('Please select a booking type and date');
            return;
        }
        if (!selectedDate || !selectedStartTime || !selectedEndTime) {
            alert('Please select a date and time slot');
            return;
        }
        
        // Validation logic...
        
        const requestData = {
            step: 2,
            data: {
                space_id: spaceId,
                resource_id: resourceId, // ADDED for validation
                location_id: <?= $space['property_id'] ?? 0 ?>,
                booking_type: bookingType,
                date: selectedDate,
                end_date: selectedEndDate || selectedDate,
                start_time: selectedStartTime,
                end_time: selectedEndTime,
                duration: selectedDuration, // Optional: useful for backend
                is_recurring: isRecurring ? 1 : 0,
                recurring_pattern: recurringPattern || '',
                recurring_end_date: recurringEndDate || ''
            }
        };
        
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
                window.location.href = `<?= base_url('booking-wizard/step3/') ?>${resourceId}`; 
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

