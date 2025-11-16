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
                            <h4 class="card-title mb-4"><?= htmlspecialchars($resource['facility_name'] ?? 'Resource') ?></h4>
                            
                            <!-- Date Picker -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Date</label>
                                <input type="date" id="booking_date" class="form-control form-control-lg" 
                                       min="<?= date('Y-m-d') ?>" 
                                       value="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- Time Slot Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Available Time Slots</label>
                                <div id="time-slots-container" class="row g-2">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i> Please select a date to see available time slots
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Time Summary -->
                            <div id="selected-time-summary" class="alert alert-success" style="display: none;">
                                <h6><i class="bi bi-check-circle"></i> Selected Time Slot</h6>
                                <p class="mb-0">
                                    <strong>Date:</strong> <span id="selected-date"></span><br>
                                    <strong>Time:</strong> <span id="selected-time"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Resource Info Sidebar -->
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <h5 class="card-title">Resource Details</h5>
                            
                            <?php if (!empty($photos)): ?>
                                <img src="<?= base_url($photos[0]['photo_path'] ?? '') ?>" class="img-fluid rounded mb-3" alt="Resource Image">
                            <?php endif; ?>
                            
                            <p class="text-muted small"><?= htmlspecialchars($resource['description'] ?? '') ?></p>
                            
                            <div class="mb-3">
                                <strong>Capacity:</strong> <?= $resource['capacity'] ?? 'N/A' ?> people<br>
                                <strong>Min Duration:</strong> <?= $resource['minimum_duration'] ?? 1 ?> hour(s)
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
                                <strong>Starting from:</strong><br>
                                <span class="h4 text-primary"><?= format_currency($resource['hourly_rate'] ?? 0) ?></span>
                                <small class="text-muted">/hour</small>
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
    
    const resourceId = <?= $resource['id'] ?? 0 ?>;
    let selectedDate = '';
    let selectedStartTime = '';
    let selectedEndTime = '';

    // Load time slots when date changes
    bookingDate.addEventListener('change', function() {
        const date = this.value;
        if (!date) return;
        
        selectedDate = date;
        loadTimeSlots(resourceId, date);
    });

    // Load initial slots
    if (bookingDate.value) {
        loadTimeSlots(resourceId, bookingDate.value);
    }

    function loadTimeSlots(resourceId, date) {
        timeSlotsContainer.innerHTML = '<div class="col-12"><div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div></div>';
        
        fetch(`<?= base_url('booking-wizard/get-time-slots') ?>?resource_id=${resourceId}&date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.slots.length > 0) {
                    let html = '';
                    data.slots.forEach(slot => {
                        html += `
                            <div class="col-md-6 col-lg-4">
                                <button type="button" class="btn btn-primary w-100 time-slot-btn" 
                                        data-start="${slot.start}" 
                                        data-end="${slot.end}"
                                        style="min-height: 60px;">
                                    ${slot.display}
                                </button>
                            </div>
                        `;
                    });
                    timeSlotsContainer.innerHTML = html;
                    
                    // Add click handlers
                    document.querySelectorAll('.time-slot-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // Remove active class from all buttons
                            document.querySelectorAll('.time-slot-btn').forEach(b => {
                                b.classList.remove('active');
                                b.classList.remove('btn-primary');
                                b.classList.add('btn-primary');
                            });
                            
                            // Add active class to selected
                            this.classList.add('active');
                            this.classList.remove('btn-primary');
                            this.classList.add('btn-primary');
                            
                            selectedStartTime = this.dataset.start;
                            selectedEndTime = this.dataset.end;
                            
                            // Update summary
                            selectedDateSpan.textContent = new Date(selectedDate).toLocaleDateString();
                            selectedTimeSpan.textContent = `${selectedStartTime} - ${selectedEndTime}`;
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
        if (!selectedDate || !selectedStartTime || !selectedEndTime) {
            alert('Please select a date and time slot');
            return;
        }

        // Save to session via AJAX
        fetch('<?= base_url('booking-wizard/save-step') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `step=2&data[resource_id]=${resourceId}&data[date]=${selectedDate}&data[start_time]=${selectedStartTime}&data[end_time]=${selectedEndTime}`
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

