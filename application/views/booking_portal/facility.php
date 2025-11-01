<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid py-5" style="background: #f8f9fa; min-height: 100vh;">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <a href="<?= base_url('booking-portal') ?>" class="btn btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Facilities
                </a>
                <h1 class="mb-2"><?= htmlspecialchars($facility['facility_name']) ?></h1>
                <p class="text-muted"><?= htmlspecialchars($facility['facility_code']) ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <?php if ($facility['description']): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($facility['description'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Book This Facility</h5>
                        <form id="bookingForm">
                            <input type="hidden" name="facility_id" value="<?= $facility['id'] ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Booking Date *</label>
                                    <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="checkAvailability()">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Booking Type *</label>
                                    <select name="booking_type" id="booking_type" class="form-select" required onchange="updateTimeSlots()">
                                        <option value="hourly">Hourly</option>
                                        <option value="daily">Daily</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Available Time Slots</label>
                                <div id="timeSlots" class="time-slots-container">
                                    <p class="text-muted">Please select a date first</p>
                                </div>
                            </div>

                            <div id="selectedSlotInfo" class="alert alert-info" style="display: none;">
                                <strong>Selected:</strong> <span id="selectedSlotText"></span><br>
                                <strong>Estimated Price:</strong> <span id="estimatedPrice">₦0.00</span>
                            </div>

                            <hr>

                            <h6 class="mb-3">Your Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="customer_email" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="text" name="customer_phone" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Number of Guests</label>
                                    <input type="number" name="number_of_guests" class="form-control" min="0" value="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="customer_address" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Special Requests</label>
                                <textarea name="special_requests" class="form-control" rows="3"></textarea>
                            </div>

                            <input type="hidden" name="start_time" id="start_time">
                            <input type="hidden" name="end_time" id="end_time">

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-calendar-check"></i> Submit Booking Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Facility Details</h5>
                        <div class="mb-2">
                            <strong>Capacity:</strong> <?= $facility['capacity'] ?> people
                        </div>
                        <div class="mb-2">
                            <strong>Minimum Duration:</strong> <?= $facility['minimum_duration'] ?> hour(s)
                        </div>
                        <div class="mb-2">
                            <strong>Hourly Rate:</strong> <?= format_currency($facility['hourly_rate']) ?>
                        </div>
                        <?php if ($facility['daily_rate'] > 0): ?>
                            <div class="mb-2">
                                <strong>Daily Rate:</strong> <?= format_currency($facility['daily_rate']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($facility['security_deposit'] > 0): ?>
                            <div class="mb-2">
                                <strong>Security Deposit:</strong> <?= format_currency($facility['security_deposit']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Booking Submitted!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Your booking request has been submitted successfully.</p>
                <p><strong>Booking Number:</strong> <span id="bookingNumber"></span></p>
                <p>We will contact you shortly to confirm your booking.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.location.href='<?= base_url('booking-portal') ?>'">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSlot = null;

function checkAvailability() {
    const facilityId = document.querySelector('input[name="facility_id"]').value;
    const date = document.getElementById('booking_date').value;
    
    if (!date) {
        document.getElementById('timeSlots').innerHTML = '<p class="text-muted">Please select a date first</p>';
        return;
    }

    document.getElementById('timeSlots').innerHTML = '<p class="text-muted">Loading available slots...</p>';

    fetch('<?= base_url('booking-portal/check-availability') ?>?facility_id=' + facilityId + '&date=' + date)
        .then(response => response.json())
        .then(data => {
            if (data.available && data.slots.length > 0) {
                let html = '<div class="row g-2">';
                data.slots.forEach(slot => {
                    html += `<div class="col-md-4">
                        <button type="button" class="btn btn-outline-primary w-100 time-slot-btn" 
                                data-start="${slot.start}" data-end="${slot.end}"
                                onclick="selectSlot('${slot.start}', '${slot.end}')">
                            ${slot.display}
                        </button>
                    </div>`;
                });
                html += '</div>';
                document.getElementById('timeSlots').innerHTML = html;
            } else {
                document.getElementById('timeSlots').innerHTML = '<p class="text-danger">No available slots for this date</p>';
            }
        })
        .catch(error => {
            document.getElementById('timeSlots').innerHTML = '<p class="text-danger">Error loading availability</p>';
        });
}

function selectSlot(start, end) {
    selectedSlot = { start, end };
    document.getElementById('start_time').value = start;
    document.getElementById('end_time').value = end;
    document.getElementById('selectedSlotText').textContent = formatTime(start) + ' - ' + formatTime(end);
    
    // Update active state
    document.querySelectorAll('.time-slot-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Calculate price
    calculatePrice();
    document.getElementById('selectedSlotInfo').style.display = 'block';
}

function calculatePrice() {
    if (!selectedSlot) return;
    
    const facilityId = document.querySelector('input[name="facility_id"]').value;
    const date = document.getElementById('booking_date').value;
    const bookingType = document.getElementById('booking_type').value;
    
    // This would ideally make an API call, but for now we'll estimate
    fetch('<?= base_url('booking-portal/calculate-price') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `facility_id=${facilityId}&date=${date}&start_time=${selectedSlot.start}&end_time=${selectedSlot.end}&booking_type=${bookingType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('estimatedPrice').textContent = '₦' + parseFloat(data.price).toFixed(2);
        }
    })
    .catch(() => {
        // Fallback - estimate based on hourly rate
    });
}

function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return displayHour + ':' + minutes + ' ' + ampm;
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedSlot) {
        alert('Please select a time slot');
        return;
    }

    const formData = new FormData(this);
    
    fetch('<?= base_url('booking-portal/submit') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('bookingNumber').textContent = data.booking_number;
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        } else {
            alert(data.message || 'Failed to submit booking');
        }
    })
    .catch(error => {
        alert('Error submitting booking. Please try again.');
    });
});

function updateTimeSlots() {
    if (document.getElementById('booking_date').value) {
        checkAvailability();
    }
}
</script>

<style>
.time-slot-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
.time-slots-container {
    min-height: 100px;
}
</style>

