<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Booking</h1>
        <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="bookingForm">
                <?php echo csrf_field(); ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" id="facility_id" class="form-select" required onchange="loadFacilityDetails()">
                                <option value="">Select Facility</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?= $facility['id'] ?>" 
                                            data-hourly="<?= $facility['hourly_rate'] ?>"
                                            data-daily="<?= $facility['daily_rate'] ?>"
                                            data-weekend="<?= $facility['weekend_rate'] ?>"
                                            data-peak="<?= $facility['peak_rate'] ?>"
                                            data-deposit="<?= $facility['security_deposit'] ?>"
                                            data-min-duration="<?= $facility['minimum_duration'] ?>">
                                        <?= htmlspecialchars($facility['facility_name']) ?> (<?= htmlspecialchars($facility['facility_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Booking Type *</label>
                            <select name="booking_type" id="booking_type" class="form-select" required onchange="calculatePrice()">
                                <option value="hourly">Hourly</option>
                                <option value="daily">Daily</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Booking Date *</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required onchange="calculatePrice(); checkAvailability()">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info" id="pricePreview" style="display: none;">
                    <strong>Estimated Price:</strong> <span id="estimatedPrice">₦0.00</span>
                </div>

                <h5 class="mb-3">Customer Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Number of Guests</label>
                            <input type="number" name="number_of_guests" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="customer_phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                    <a href="<?= base_url('bookings') ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadFacilityDetails() {
    calculatePrice();
}

function calculatePrice() {
    const facilitySelect = document.getElementById('facility_id');
    const facilityOption = facilitySelect.options[facilitySelect.selectedIndex];
    
    if (!facilityOption || !facilityOption.value) {
        document.getElementById('pricePreview').style.display = 'none';
        return;
    }

    const bookingDate = document.getElementById('booking_date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const bookingType = document.getElementById('booking_type').value;
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;

    if (!bookingDate || !startTime || !endTime) {
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

    // Get rates
    const hourlyRate = parseFloat(facilityOption.dataset.hourly) || 0;
    const dailyRate = parseFloat(facilityOption.dataset.daily) || 0;
    const deposit = parseFloat(facilityOption.dataset.deposit) || 0;

    let basePrice = 0;
    if (bookingType === 'daily') {
        const days = Math.ceil(hours / 24);
        basePrice = dailyRate * days;
    } else {
        basePrice = hourlyRate * hours;
    }

    const total = basePrice - discount + deposit;

    document.getElementById('estimatedPrice').textContent = '₦' + total.toFixed(2);
    document.getElementById('pricePreview').style.display = 'block';
}

function checkAvailability() {
    // This would make an AJAX call to check availability
    // For now, it's handled server-side during form submission
}
</script>

